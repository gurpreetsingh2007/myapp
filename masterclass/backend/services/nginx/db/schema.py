import matplotlib.pyplot as plt
import networkx as nx

# Create a directed graph
G = nx.DiGraph()

# Define tables (nodes)
tables = [
    "certificates", "params", "locations", "reverse_proxy",
    "location_params", "server_locations", "server_params"
]

# Add nodes
for table in tables:
    G.add_node(table)

# Define foreign key relationships (edges)
relationships = [
    ("location_params", "locations"),
    ("location_params", "params"),
    ("server_locations", "reverse_proxy"),
    ("server_locations", "locations"),
    ("server_params", "reverse_proxy"),
    ("server_params", "params"),
    ("reverse_proxy", "certificates")  # SSL certificate reference
]

# Add edges
for src, dst in relationships:
    G.add_edge(src, dst)

# Draw the graph
plt.figure(figsize=(12, 8))
pos = nx.spring_layout(G, k=1.2, iterations=100)

# Draw nodes, edges, and labels
nx.draw_networkx_nodes(G, pos, node_color='lightblue', node_size=2500)
nx.draw_networkx_edges(G, pos, edge_color='gray', arrows=True)
nx.draw_networkx_labels(G, pos, font_size=10, font_weight='bold')

plt.title("NGINX Reverse Proxy DB Schema")
plt.axis('off')
plt.tight_layout()
plt.show()
