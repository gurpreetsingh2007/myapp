let apiEndpoint: string

// Check if the application is running on localhost.
if (window.location.hostname === 'localhost') {
  // If it is, use the specific backend URL for local development.
  apiEndpoint = 'https://myapp.local/backend'
} else {
  // Otherwise, dynamically construct the URL based on the current server's location.
  apiEndpoint = `${window.location.protocol}//${window.location.hostname}${window.location.port ? `:${window.location.port}` : ''}/backend`
}

// Export the final value as a constant.
export const API = apiEndpoint
