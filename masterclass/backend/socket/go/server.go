package main

import (
    "crypto/tls"
    "crypto/x509"
    "encoding/json"
    "flag"
    "fmt"
    "io/ioutil"
    "log"
    "os"
    "os/exec"
    "time"

    "github.com/gorilla/websocket"
)

type ConfigPayload struct {
    Filename string `json:"filename"`
    Config   string `json:"config"`
}

// Incoming message structure from server
type IncomingMessage struct {
    RequestId string          `json:"requestId"`
    Command   string          `json:"command"`
    Data      []ConfigPayload `json:"data"`
}

// Response structure to send back to server
type ResponseMessage struct {
    RequestId string       `json:"requestId"`
    Data      ResponseData `json:"data"`
}

type ResponseData struct {
    Command string `json:"command"`
    Output  string `json:"output"`
    Status  string `json:"status"`
}

func main() {
    serverURL := flag.String("url", "wss://myapp.local:5173/ws", "WebSocket URL")
    caCertPath := flag.String("ca", "goCert/ca.crt", "CA certificate file")
    clientCertPath := flag.String("cert", "goCert/client.crt", "Client cert file")
    clientKeyPath := flag.String("key", "goCert/client.key", "Client key file")
    flag.Parse()


    caCertPEM, err := ioutil.ReadFile(*caCertPath)
    if err != nil {
        log.Fatalf("Failed to read CA file: %v", err)
    }
    roots := x509.NewCertPool()
    if ok := roots.AppendCertsFromPEM(caCertPEM); !ok {
        log.Fatalf("Failed to parse CA cert")
    }

    clientCert, err := tls.LoadX509KeyPair(*clientCertPath, *clientKeyPath)
    if err != nil {
        log.Fatalf("Failed to load client cert/key: %v", err)
    }

    tlsConfig := &tls.Config{
        Certificates: []tls.Certificate{clientCert},
        RootCAs:      roots,
        ServerName:   "myapp.local",
    }

    dialer := websocket.Dialer{
        TLSClientConfig:  tlsConfig,
        HandshakeTimeout: 10 * time.Second,
    }

    fmt.Printf("Connecting to %s …\n", *serverURL)
    conn, _, err := dialer.Dial(*serverURL, nil)
    if err != nil {
        log.Fatalf("WebSocket dial failed: %v", err)
    }
    defer conn.Close()
    fmt.Println("✅ Connected successfully!")

    for {
        msgType, msg, err := conn.ReadMessage()
        if err != nil {
            log.Printf("Read error: %v", err)
            os.Exit(1)
        }

        if msgType != websocket.TextMessage {
            log.Printf("Ignoring non-text message of type %d", msgType)
            continue
        }

        // Parse the incoming message structure
        var incomingMsg IncomingMessage
        if err := json.Unmarshal(msg, &incomingMsg); err != nil {
            log.Printf("❌ Failed to parse incoming message structure: %v\nMessage: %s", err, string(msg))
            continue
        }

        log.Printf("📨 Received message with requestId: %s, command: %s", incomingMsg.RequestId, incomingMsg.Command)

        scriptPath := "./sc/null.sh"
        args := []string{}

        // Use the command from the incoming message structure
        switch incomingMsg.Command {
        case "0":
            // For command 0, check if we have config data to pass as argument
            if len(incomingMsg.Data) > 0 && incomingMsg.Data[0].Config != "" {
                scriptPath = "./sc/script0.sh"
                args = []string{incomingMsg.Data[0].Config}
            } else {
                log.Println("⚠️ Config data is required for command 0")
                continue
            }
        case "1":
            scriptPath = "./sc/script1.sh"
            // Pass config data as arguments if available
            for _, configPayload := range incomingMsg.Data {
                args = append(args, configPayload.Filename, configPayload.Config)
            }
        case "2":
            if len(incomingMsg.Data) > 0 && incomingMsg.Data[0].Config != "" {
                scriptPath = "./sc/script2.sh"
                args = []string{incomingMsg.Data[0].Config}
            } else {
                log.Println("⚠️ Config data is required for command 2")
                continue
            }
        case "3":
            scriptPath = "./sc/script3.sh"
        case "-1":
            scriptPath = "./sc/null.sh"
        default:
            scriptPath = "./sc/null.sh"
            log.Printf("⚠️ Using default script for unknown command: %s", incomingMsg.Command)
        }

        log.Printf("🚀 Executing script: %s with args: %v", scriptPath, args)
        cmd := exec.Command("sh", append([]string{scriptPath}, args...)...)
        output, err := cmd.CombinedOutput()

        responseData := ResponseData{
            Command: incomingMsg.Command,
            Output:  string(output),
        }

        if err != nil {
            log.Printf("❌ Script failed (command %s): %v\nOutput:\n%s", incomingMsg.Command, err, output)
            responseData.Status = "failed"
        } else {
            log.Printf("✅ Script succeeded (command %s):\n%s", incomingMsg.Command, output)
            responseData.Status = "success"
        }

        // Create the response message with the correct structure
        response := ResponseMessage{
            RequestId: incomingMsg.RequestId,
            Data:      responseData,
        }

        log.Printf("📤 Sending response with requestId: %s", response.RequestId)
        if writeErr := conn.WriteJSON(response); writeErr != nil {
            log.Printf("Failed to send response: %v", writeErr)
        }
    }
}