$diagrams = @{
    'System Architecture Diagram' = @'
flowchart LR
    A[User Browser] -->|HTTP(S)| B[Web Server (PHP)]
    B -->|Queries| C[(MySQL Database)]
    B -->|Serves HTML/CSS/JS| A
    subgraph Localhost (XAMPP)
      B
      C
    end
'@
    'Level 0 Data Flow Diagram (Context Diagram)' = @'
flowchart LR
    User[(User)] -->|Register / Login / Post Idea| System[EntreNet Africa System]
    Admin[(Admin)] -->|Manage Users / Reports| System
    System -->|Display Pages / Results| User
    System -->|Display Admin Dashboard| Admin
'@
    'Level 1 Data Flow Diagram' = @'
flowchart LR
    subgraph External
      U[User]
      A[Admin]
    end
    subgraph Processes
      P1[Authenticate / Session]
      P2[Idea Posting / Browsing]
      P3[Review & Report]
      P4[Admin Management]
    end
    subgraph DataStores
      D1[(Users)]
      D2[(Posts)]
      D3[(Reviews)]
      D4[(Reports)]
    end
    U -->|login/register| P1
    P1 --> D1
    U -->|post idea/view ideas| P2
    P2 --> D2
    U -->|write review/report| P3
    P3 --> D3
    P3 --> D4
    A -->|manage users/posts/reports| P4
    P4 --> D1
    P4 --> D2
    P4 --> D3
    P4 --> D4
'@
    'Use Case Diagram' = @'
usecaseDiagram
    actor User
    actor Admin
    User -- (Register)
    User -- (Login)
    User -- (Create Idea Post)
    User -- (Browse Ideas)
    User -- (Review Idea)
    User -- (Report Content)
    User -- (Edit Profile)
    Admin -- (Manage Users)
    Admin -- (Moderate Content)
    Admin -- (View Reports)
'@
    'Entity Relationship Diagram (ERD)' = @'
erDiagram
    USERS {
      int user_id PK
      string full_name
      string email
      string password_hash
      string role
      bool is_admin
      datetime last_login
    }
    POSTS {
      int post_id PK
      int user_id FK
      string title
      text description
      datetime created_at
    }
    REVIEWS {
      int review_id PK
      int user_id FK
      int post_id FK
      int rating
      text review_text
      datetime created_at
    }
    REPORTS {
      int report_id PK
      int user_id FK
      int post_id FK
      text report_text
      datetime reported_at
    }
    USERS ||--o{ POSTS : "creates"
    USERS ||--o{ REVIEWS : "writes"
    USERS ||--o{ REPORTS : "files"
    POSTS ||--o{ REVIEWS : "receives"
    POSTS ||--o{ REPORTS : "is reported in"
'@
    'Activity Diagram for User Idea Posting' = @'
flowchart TD
    Start((Start)) --> Login["Login/Authenticate"]
    Login -->|Success| Dashboard["View Dashboard"]
    Dashboard --> Create["Create New Idea"]
    Create --> Input["Enter Title & Description"]
    Input --> Submit["Submit Idea"]
    Submit --> Save["Save to Database"]
    Save --> Confirm["Show Confirmation"]
    Confirm --> End((End))
    Login -->|Fail| Error["Show Error"] --> End
'@
    'Sequence Diagram for User Login' = @'
sequenceDiagram
    participant User
    participant Browser
    participant Server
    participant Database
    User->>Browser: Enter credentials
    Browser->>Server: POST /login
    Server->>Database: SELECT user WHERE full_name
    Database-->>Server: User record
    Server->>Server: Verify password
    alt password valid
      Server->>Database: UPDATE last_login
      Server-->>Browser: Set session cookie, redirect
      Browser-->>User: Redirect to dashboard
    else invalid
      Server-->>Browser: Error message
      Browser-->>User: Show error
    end
'@
    'Class Diagram (if applicable)' = @'
classDiagram
    class User {
      +int user_id
      +string full_name
      +string email
      +string password_hash
      +string role
      +bool is_admin
      +datetime last_login
      +login()
      +logout()
      +editProfile()
    }
    class IdeaPost {
      +int post_id
      +int user_id
      +string title
      +string description
      +datetime created_at
      +createPost()
      +editPost()
    }
    class Review {
      +int review_id
      +int user_id
      +int post_id
      +int rating
      +string review_text
      +datetime created_at
      +submitReview()
    }
    class Report {
      +int report_id
      +int user_id
      +int post_id
      +string report_text
      +datetime reported_at
      +submitReport()
    }
    User "1" -- "*" IdeaPost : creates
    User "1" -- "*" Review : writes
    User "1" -- "*" Report : files
    IdeaPost "1" -- "*" Review : receives
    IdeaPost "1" -- "*" Report : isReportedIn
'@
}

function Make-Link($code) {
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($code)
    $ms = New-Object System.IO.MemoryStream
    $ds = New-Object System.IO.Compression.DeflateStream($ms, [System.IO.Compression.CompressionMode]::Compress)
    $ds.Write($bytes, 0, $bytes.Length)
    $ds.Close()
    $compressed = $ms.ToArray()
    $zlib = [byte[]](0x78,0x9c) + $compressed
    $b64 = [Convert]::ToBase64String($zlib).TrimEnd('=') -replace '\+', '-' -replace '/', '_'
    return "https://mermaid.live/edit#pako:$b64"
}

$out = ""
foreach ($entry in $diagrams.GetEnumerator()) {
    $out += "=== $($entry.Key) ===`n"
    $out += "$($entry.Value)`n"
    $out += "Mermaid Live Editor: $(Make-Link $entry.Value)`n`n"
}

$out | Out-File -FilePath diagram_links.txt -Encoding utf8
Write-Host "Diagram links written to diagram_links.txt"
