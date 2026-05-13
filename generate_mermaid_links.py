import zlib, base64

codes = {
    'System Architecture Diagram': "flowchart LR\n    A[User Browser] -->|HTTP(S)| B[Web Server (PHP)]\n    B -->|Queries| C[(MySQL Database)]\n    B -->|Serves HTML/CSS/JS| A\n    subgraph Localhost (XAMPP)\n      B\n      C\n    end\n",

    'Level 0 Data Flow Diagram (Context Diagram)': "flowchart LR\n    User[(User)] -->|Register / Login / Post Idea| System[EntreNet Africa System]\n    Admin[(Admin)] -->|Manage Users / Reports| System\n    System -->|Display Pages / Results| User\n    System -->|Display Admin Dashboard| Admin\n",

    'Level 1 Data Flow Diagram': "flowchart LR\n    subgraph External\n      U[User]\n      A[Admin]\n    end\n    subgraph Processes\n      P1[Authenticate / Session]\n      P2[Idea Posting / Browsing]\n      P3[Review & Report]\n      P4[Admin Management]\n    end\n    subgraph DataStores\n      D1[(Users)]\n      D2[(Posts)]\n      D3[(Reviews)]\n      D4[(Reports)]\n    end\n    U -->|login/register| P1\n    P1 --> D1\n    U -->|post idea/view ideas| P2\n    P2 --> D2\n    U -->|write review/report| P3\n    P3 --> D3\n    P3 --> D4\n    A -->|manage users/posts/reports| P4\n    P4 --> D1\n    P4 --> D2\n    P4 --> D3\n    P4 --> D4\n",

    'Use Case Diagram': "usecaseDiagram\n    actor User\n    actor Admin\n    User -- (Register)\n    User -- (Login)\n    User -- (Create Idea Post)\n    User -- (Browse Ideas)\n    User -- (Review Idea)\n    User -- (Report Content)\n    User -- (Edit Profile)\n    Admin -- (Manage Users)\n    Admin -- (Moderate Content)\n    Admin -- (View Reports)\n",

    'Entity Relationship Diagram (ERD)': "erDiagram\n    USERS {\n      int user_id PK\n      string full_name\n      string email\n      string password_hash\n      string role\n      bool is_admin\n      datetime last_login\n    }\n    POSTS {\n      int post_id PK\n      int user_id FK\n      string title\n      text description\n      datetime created_at\n    }\n    REVIEWS {\n      int review_id PK\n      int user_id FK\n      int post_id FK\n      int rating\n      text review_text\n      datetime created_at\n    }\n    REPORTS {\n      int report_id PK\n      int user_id FK\n      int post_id FK\n      text report_text\n      datetime reported_at\n    }\n    USERS ||--o{ POSTS : \"creates\"\n    USERS ||--o{ REVIEWS : \"writes\"\n    USERS ||--o{ REPORTS : \"files\"\n    POSTS ||--o{ REVIEWS : \"receives\"\n    POSTS ||--o{ REPORTS : \"is reported in\"\n",

    'Activity Diagram for User Idea Posting': "flowchart TD\n    Start((Start)) --> Login[\"Login/Authenticate\"]\n    Login -->|Success| Dashboard[\"View Dashboard\"]\n    Dashboard --> Create[\"Create New Idea\"]\n    Create --> Input[\"Enter Title & Description\"]\n    Input --> Submit[\"Submit Idea\"]\n    Submit --> Save[\"Save to Database\"]\n    Save --> Confirm[\"Show Confirmation\"]\n    Confirm --> End((End))\n    Login -->|Fail| Error[\"Show Error\"] --> End\n",

    'Sequence Diagram for User Login': "sequenceDiagram\n    participant User\n    participant Browser\n    participant Server\n    participant Database\n    User->>Browser: Enter credentials\n    Browser->>Server: POST /login\n    Server->>Database: SELECT user WHERE full_name\n    Database-->>Server: User record\n    Server->>Server: Verify password\n    alt password valid\n      Server->>Database: UPDATE last_login\n      Server-->>Browser: Set session cookie, redirect\n      Browser-->>User: Redirect to dashboard\n    else invalid\n      Server-->>Browser: Error message\n      Browser-->>User: Show error\n    end\n",

    'Class Diagram (if applicable)': "classDiagram\n    class User {\n      +int user_id\n      +string full_name\n      +string email\n      +string password_hash\n      +string role\n      +bool is_admin\n      +datetime last_login\n      +login()\n      +logout()\n      +editProfile()\n    }\n    class IdeaPost {\n      +int post_id\n      +int user_id\n      +string title\n      +string description\n      +datetime created_at\n      +createPost()\n      +editPost()\n    }\n    class Review {\n      +int review_id\n      +int user_id\n      +int post_id\n      +int rating\n      +string review_text\n      +datetime created_at\n      +submitReview()\n    }\n    class Report {\n      +int report_id\n      +int user_id\n      +int post_id\n      +string report_text\n      +datetime reported_at\n      +submitReport()\n    }\n    User \"1\" -- \"*\" IdeaPost : creates\n    User \"1\" -- \"*\" Review : writes\n    User \"1\" -- \"*\" Report : files\n    IdeaPost \"1\" -- \"*\" Review : receives\n    IdeaPost \"1\" -- \"*\" Report : isReportedIn\n"
}


def make_link(code):
    compressed = zlib.compress(code.encode('utf-8'))
    b64 = base64.urlsafe_b64encode(compressed).decode('ascii').rstrip('=')
    return f'https://mermaid.live/edit#pako:{b64}'

with open('diagram_links.txt', 'w', encoding='utf-8') as f:
    for name, code in codes.items():
        link = make_link(code)
        f.write(f"=== {name} ===\n")
        f.write(code + "\n")
        f.write(f"Mermaid Live Editor: {link}\n\n")

print('Links written to diagram_links.txt')
