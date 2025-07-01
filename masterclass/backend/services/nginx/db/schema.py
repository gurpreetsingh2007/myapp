import matplotlib.pyplot as plt
import networkx as nx

# Define tables and their foreign key relationships
updated_tables = [
    "nginx_scans",
    "ssl_certificates",
    "certificate_validations",
    "ssl_certificate_domains",
    "nginx_config_files",
    "nginx_http_blocks",
    "nginx_servers",
    "nginx_locations",
    "nginx_ssl_certificates",
    "nginx_main_directives",
    "nginx_http_directives",
    "nginx_server_directives",
    "nginx_location_directives",
    "nginx_proxy_targets",
    "nginx_upstreams",
    "nginx_upstream_servers",
    "nginx_static_file_mappings",
    "nginx_location_optimizations"
]

# Define foreign key relationships as (child_table, parent_table)
updated_relationships = [
    ("ssl_certificates", "nginx_scans"),
    ("certificate_validations", "ssl_certificates"),
    ("ssl_certificate_domains", "ssl_certificates"),
    ("nginx_config_files", "nginx_scans"),
    ("nginx_http_blocks", "nginx_config_files"),
    ("nginx_servers", "nginx_config_files"),
    ("nginx_servers", "nginx_http_blocks"),
    ("nginx_locations", "nginx_servers"),
    ("nginx_ssl_certificates", "nginx_servers"),
    ("nginx_ssl_certificates", "ssl_certificates"),
    ("nginx_main_directives", "nginx_config_files"),
    ("nginx_http_directives", "nginx_http_blocks"),
    ("nginx_server_directives", "nginx_servers"),
    ("nginx_location_directives", "nginx_locations"),
    ("nginx_proxy_targets", "nginx_locations"),
    ("nginx_static_file_mappings", "nginx_locations"),
    ("nginx_location_optimizations", "nginx_locations"),
    ("nginx_upstreams", "nginx_config_files"),
    ("nginx_upstream_servers", "nginx_upstreams")
]

# Create a directed graph
G_updated = nx.DiGraph()

# Add nodes (tables)
G_updated.add_nodes_from(updated_tables)

# Add edges (foreign key relationships)
G_updated.add_edges_from(updated_relationships)

# Set plot size and layout parameters
plt.figure(figsize=(16, 12))
pos = nx.spring_layout(G_updated, k=1, iterations=29700, seed=150729153)

# Draw nodes and edges
nx.draw(
    G_updated,
    pos,
    with_labels=True,
    node_size=3000,
    node_color='lightgreen',
    font_size=9,
    font_weight='bold',
    edge_color='gray',
    arrowsize=20
)

# Add plot title and layout adjustments
plt.title("Expanded MariaDB Schema for NGINX Configuration Portal", fontsize=16)
plt.tight_layout()
plt.show()
