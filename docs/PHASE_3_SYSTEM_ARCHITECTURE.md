# StudyForge — AI Study Companion
## Phase 3: System Architecture Design

---

## Executive Summary

This document defines the technical architecture for StudyForge, ensuring a scalable, maintainable, and student-developer-friendly system. The architecture follows Laravel best practices while keeping complexity manageable for a solo developer.

**Key Principles:**
- Simple but scalable
- Clear separation of concerns
- Easy to understand and maintain
- Production-ready from day one
- Minimal infrastructure complexity

---

## 1. Overall System Architecture

### High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                         │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              Browser (Desktop/Mobile)                │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐ │   │
│  │  │   Blade     │  │  Tailwind   │  │  Alpine.js  │ │   │
│  │  │  Templates  │  │    CSS      │  │   Interactivity │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘ │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ HTTP/HTTPS
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      APPLICATION LAYER                      │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                   Laravel Application               │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐ │   │
│  │  │   Routes    │  │ Controllers │  │  Middleware  │ │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘ │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐ │   │
│  │  │  Services   │  │   Models    │  │  Policies   │ │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘ │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ Eloquent ORM
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                       DATA LAYER                            │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    MySQL Database                   │   │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌────────┐│   │
│  │  │  Users  │  │Sessions │  │Outputs  │  │Quizzes ││   │
│  │  └─────────┘  └─────────┘  └─────────┘  └────────┘│   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ API Calls
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                     EXTERNAL SERVICES                       │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              AI Service (OpenAI/Claude)             │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐ │   │
│  │  │  Summary    │  │ Flashcards  │  │   Quizzes   │ │   │
│  │  │ Generation  │  │ Generation  │  │ Generation  │ │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘ │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Architecture Layers

| Layer | Technology | Purpose |
|-------|------------|---------|
| **Client** | Blade + Tailwind + Alpine.js | User interface and interaction |
| **Application** | Laravel (PHP) | Business logic, routing, data processing |
| **Data** | MySQL | Persistent data storage |
| **External** | OpenAI/Claude API | AI content generation |

---

## 2. Frontend Architecture

### Technology Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| **Blade** | Laravel 10+ | Server-side templating |
| **Tailwind CSS** | 3.x | Utility-first CSS framework |
| **Alpine.js** | 3.x | Lightweight JavaScript interactivity |
| **Vite** | 5.x | Asset bundling and hot reload |

### Frontend Structure

```
resources/
├── views/
│   ├── layouts/
│   │   ├── app.blade.php          # Main layout
│   │   ├── guest.blade.php        # Guest layout (auth pages)
│   │   └── partials/
│   │       ├── header.blade.php
│   │       ├── footer.blade.php
│   │       └── sidebar.blade.php
│   ├── auth/
│   │   ├── login.blade.php
│   │   ├── register.blade.php
│   │   └── forgot-password.blade.php
│   ├── dashboard/
│   │   └── index.blade.php
│   ├── study/
│   │   ├── create.blade.php       # New study session
│   │   ├── show.blade.php         # View session
│   │   ├── summary.blade.php      # Summary view
│   │   ├── flashcards.blade.php   # Flashcard viewer
│   │   └── quiz.blade.php         # Quiz view
│   ├── history/
│   │   └── index.blade.php
│   └── components/
│       ├── card.blade.php
│       ├── button.blade.php
│       ├── modal.blade.php
│       └── alert.blade.php
├── css/
│   └── app.css                    # Tailwind imports
└── js/
    └── app.js                     # Alpine.js components
```

### Component Architecture

**Blade Components:**
- Reusable UI elements (cards, buttons, modals)
- Consistent styling across pages
- Easy maintenance

**Alpine.js Components:**
- Flashcard flip animation
- Quiz interaction (select answer, submit)
- Modal open/close
- Form validation
- Loading states

### State Management

**Client-Side State:**
- Alpine.js `$store` for global state
- Form data in component scope
- UI state (modals, loading, errors)

**Server-Side State:**
- Laravel sessions for user auth
- Database for persistent data
- No complex state management needed

---

## 3. Backend Architecture

### Technology Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| **Laravel** | 10.x | PHP framework |
| **PHP** | 8.2+ | Backend language |
| **MySQL** | 8.0+ | Database |
| **Composer** | 2.x | Dependency management |

### Backend Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   ├── RegisterController.php
│   │   │   └── ForgotPasswordController.php
│   │   ├── DashboardController.php
│   │   ├── StudySessionController.php
│   │   ├── HistoryController.php
│   │   └── ProfileController.php
│   ├── Middleware/
│   │   ├── Authenticate.php
│   │   └── RedirectIfAuthenticated.php
│   └── Requests/
│       ├── StoreStudySessionRequest.php
│       └── UpdateProfileRequest.php
├── Models/
│   ├── User.php
│   ├── StudySession.php
│   ├── GeneratedOutput.php
│   ├── Flashcard.php
│   ├── Quiz.php
│   └── QuizQuestion.php
├── Services/
│   ├── AiService.php              # AI API integration
│   ├── SummaryGenerator.php       # Summary generation logic
│   ├── FlashcardGenerator.php     # Flashcard generation logic
│   ├── QuizGenerator.php          # Quiz generation logic
│   └── ContentProcessor.php       # Input processing
├── Policies/
│   └── StudySessionPolicy.php
└── Providers/
    └── AppServiceProvider.php
```

### MVC Pattern

**Model (Eloquent):**
- Database interaction
- Relationships
- Data validation
- Business logic

**View (Blade):**
- UI presentation
- Data display
- User interaction

**Controller:**
- Request handling
- Service coordination
- Response formatting

### Service Layer

**Why Services?**
- Keep controllers thin
- Reusable business logic
- Easier testing
- Clear separation of concerns

**Service Responsibilities:**

| Service | Responsibility |
|---------|----------------|
| **AiService** | API communication, error handling, response parsing |
| **SummaryGenerator** | Prompt creation, output formatting |
| **FlashcardGenerator** | Prompt creation, card structuring |
| **QuizGenerator** | Prompt creation, question/answer structuring |
| **ContentProcessor** | Input validation, cleaning, chunking |

---

## 4. Database Design Direction

### Database Philosophy

**Principles:**
- Normalize where it makes sense
- Denormalize for performance when needed
- Keep queries simple
- Use Laravel's Eloquent effectively
- Plan for growth but don't over-engineer

### Core Tables (Preliminary)

| Table | Purpose | Key Fields |
|-------|---------|------------|
| **users** | User accounts | id, name, email, password |
| **study_sessions** | Study session metadata | id, user_id, title, input_text |
| **generated_outputs** | AI-generated content | id, session_id, type, content |
| **flashcards** | Individual flashcards | id, session_id, question, answer |
| **quizzes** | Quiz metadata | id, session_id, title |
| **quiz_questions** | Individual quiz questions | id, quiz_id, question, options, correct_answer |

### Relationships

```
User (1) ──────► (Many) StudySession
StudySession (1) ──► (Many) GeneratedOutput
StudySession (1) ──► (Many) Flashcard
StudySession (1) ──► (Many) Quiz
Quiz (1) ──────────► (Many) QuizQuestion
```

### Data Storage Strategy

**Input Text:**
- Stored in `study_sessions` table
- Full text for reference
- Indexed for search (future)

**Generated Outputs:**
- Stored in `generated_outputs` table
- JSON format for flexibility
- Type field for filtering

**Flashcards:**
- Stored in `flashcards` table
- Individual records for easy manipulation
- Enables future features (spaced repetition)

**Quizzes:**
- Stored in `quizzes` and `quiz_questions` tables
- Structured format for rendering
- Supports future features (analytics)

---

## 5. AI Generation Flow

### AI Service Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    AiService                            │
│  ┌─────────────────────────────────────────────────┐   │
│  │              API Client                         │   │
│  │  - HTTP client (Guzzle)                         │   │
│  │  - Authentication                               │   │
│  │  - Rate limiting                                │   │
│  │  - Error handling                               │   │
│  └─────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────┐   │
│  │           Generation Methods                    │   │
│  │  - generateSummary()                            │   │
│  │  - generateFlashcards()                         │   │
│  │  - generateQuiz()                               │   │
│  │  - generateKeyTerms()                           │   │
│  └─────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────┐   │
│  │           Response Parsers                      │   │
│  │  - parseSummary()                               │   │
│  │  - parseFlashcards()                            │   │
│  │  - parseQuiz()                                  │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

### Generation Flow

```
User Input (Text)
    │
    ▼
ContentProcessor
    │
    ├─► Validate input
    ├─► Clean text
    ├─► Check length limits
    └─► Prepare for AI
    │
    ▼
AiService
    │
    ├─► Select generation type
    ├─► Build prompt
    ├─► Call AI API
    ├─► Handle response
    └─► Parse output
    │
    ▼
Generator (Summary/Flashcard/Quiz)
    │
    ├─► Format output
    ├─► Validate structure
    └─► Return to controller
    │
    ▼
Controller
    │
    ├─► Save to database
    └─► Return to view
```

### AI API Integration

**API Choice:** OpenAI GPT-4 or Claude API

**Why OpenAI/Claude?**
- Proven reliability
- Excellent instruction following
- Good JSON output support
- Reasonable pricing

**API Configuration:**
```php
// config/services.php
'openai' => [
    'key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4'),
    'max_tokens' => env('OPENAI_MAX_TOKENS', 4000),
],
```

**Rate Limiting:**
- Track API calls per user
- Implement cooldown periods
- Queue requests if needed
- Graceful degradation

---

## 6. Request-Response Flow

### Typical User Request Flow

```
1. User Action
   │
   ├─► Click "Generate Summary"
   │
   ▼
2. Browser (Alpine.js)
   │
   ├─► Show loading state
   ├─► Collect form data
   └─► Send AJAX request
   │
   ▼
3. Laravel Route
   │
   ├─► Route: POST /study-sessions/{id}/generate
   └─► Middleware: auth, throttle
   │
   ▼
4. Controller
   │
   ├─► Validate request
   ├─► Authorize action
   └─► Call service
   │
   ▼
5. Service Layer
   │
   ├─► Process content
   ├─► Call AI API
   └─► Parse response
   │
   ▼
6. Database
   │
   ├─► Save generated content
   └─► Update session
   │
   ▼
7. Response
   │
   ├─► Return JSON (AJAX)
   └─► Update UI (Alpine.js)
   │
   ▼
8. User Sees Result
   │
   └─► Summary displayed
```

### API Response Format

**Success Response:**
```json
{
  "success": true,
  "data": {
    "summary": "Generated summary text...",
    "key_points": ["Point 1", "Point 2", "Point 3"]
  },
  "message": "Summary generated successfully"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": {
    "code": "AI_GENERATION_FAILED",
    "message": "Failed to generate summary. Please try again."
  }
}
```

---

## 7. Content Processing Pipeline

### Pipeline Stages

```
Stage 1: Input Validation
    │
    ├─► Check if text is provided
    ├─► Check minimum length (100 chars)
    ├─► Check maximum length (50,000 chars)
    └─► Sanitize input
    │
    ▼
Stage 2: Content Cleaning
    │
    ├─► Remove excessive whitespace
    ├─► Normalize line breaks
    ├─► Remove special characters (if needed)
    └─► Preserve formatting where important
    │
    ▼
Stage 3: Content Analysis
    │
    ├─► Detect language (future)
    ├─► Estimate reading time
    ├─► Count words/characters
    └─► Identify content type (future)
    │
    ▼
Stage 4: Chunking (if needed)
    │
    ├─► Split long content into chunks
    ├─► Maintain context between chunks
    └─► Prepare for AI processing
    │
    ▼
Stage 5: AI Processing
    │
    ├─► Send to AI service
    ├─► Receive response
    └─► Parse output
    │
    ▼
Stage 6: Output Formatting
    │
    ├─► Structure data
    ├─► Validate format
    └─► Prepare for storage
```

### ContentProcessor Service

```php
class ContentProcessor
{
    public function validate(string $input): bool
    public function clean(string $input): string
    public function analyze(string $input): array
    public function chunk(string $input, int $maxChunkSize): array
}
```

---

## 8. How Generated Outputs Should Be Stored

### Storage Strategy

**Option 1: Single Table with JSON (Recommended for MVP)**
- Store all outputs in `generated_outputs` table
- Use JSON column for flexible content
- Type field to distinguish output types
- Simple, flexible, easy to query

**Option 2: Separate Tables per Type**
- `summaries` table
- `flashcards` table
- `quizzes` table
- More structured, better for complex queries

**Recommendation:** Option 1 for MVP, migrate to Option 2 if needed

### Generated Outputs Table Structure

```sql
generated_outputs
├── id (bigint, primary key)
├── study_session_id (bigint, foreign key)
├── type (enum: summary, flashcards, quiz, key_terms)
├── content (json)
├── metadata (json) -- word count, generation time, etc.
├── created_at (timestamp)
└── updated_at (timestamp)
```

### JSON Content Examples

**Summary Output:**
```json
{
  "summary": "Full summary text...",
  "key_points": ["Point 1", "Point 2", "Point 3"],
  "word_count": 250
}
```

**Flashcards Output:**
```json
{
  "cards": [
    {
      "question": "What is photosynthesis?",
      "answer": "The process by which plants convert sunlight..."
    }
  ],
  "total_cards": 15
}
```

**Quiz Output:**
```json
{
  "questions": [
    {
      "question": "What is the powerhouse of the cell?",
      "options": {
        "A": "Nucleus",
        "B": "Mitochondria",
        "C": "Ribosome",
        "D": "Golgi apparatus"
      },
      "correct": "B",
      "explanation": "Mitochondria produce ATP..."
    }
  ],
  "total_questions": 10
}
```

---

## 9. How User History Should Work

### History Features

**Core Features:**
- List all study sessions
- Search by title
- Filter by date
- Sort by newest/oldest
- Quick preview
- Delete sessions

**Data Display:**
- Session title
- Creation date
- Content types generated
- Input text preview
- Quick actions (view, delete)

### History Query Strategy

**Efficient Queries:**
```php
// Get user's sessions with pagination
StudySession::where('user_id', auth()->id())
    ->orderBy('created_at', 'desc')
    ->paginate(20);

// Search sessions
StudySession::where('user_id', auth()->id())
    ->where('title', 'like', "%{$search}%")
    ->orderBy('created_at', 'desc')
    ->get();

// Get session with outputs
StudySession::with('generatedOutputs')
    ->findOrFail($id);
```

### History Page Components

**Session Card:**
- Title
- Date
- Content type badges
- Preview text
- Action buttons

**Search & Filter:**
- Search input
- Date range filter
- Content type filter
- Sort options

**Pagination:**
- Page numbers
- Previous/Next buttons
- Items per page selector

---

## 10. How Exports Should Work (Future)

### Export Strategy (Version 2.0)

**Export Formats:**
- PDF (formatted document)
- TXT (plain text)
- JSON (structured data)

**Export Process:**
```
1. User clicks "Export"
    │
    ▼
2. Select format (PDF/TXT/JSON)
    │
    ▼
3. Backend generates file
    │
    ├─► PDF: Use DomPDF or Snappy
    ├─► TXT: Simple text formatting
    └─► JSON: Direct data export
    │
    ▼
4. Download file
```

**PDF Generation:**
- Use Laravel DomPDF package
- Create Blade template for PDF
- Style with CSS
- Include headers/footers

**Print-Friendly View:**
- CSS `@media print` rules
- Hide navigation/buttons
- Optimize for paper
- Clean, readable layout

---

## 11. How to Keep the App Manageable for a Student Developer

### Simplicity Principles

**1. Follow Laravel Conventions**
- Use standard Laravel patterns
- Don't reinvent the wheel
- Leverage built-in features
- Follow naming conventions

**2. Keep Code Organized**
- Clear directory structure
- Logical file naming
- Consistent coding style
- Well-commented code

**3. Minimize Dependencies**
- Use Laravel's built-in features
- Avoid unnecessary packages
- Keep composer.json lean
- Only add what's truly needed

**4. Simple Database Design**
- Start with essential tables
- Add columns as needed
- Don't over-normalize
- Use migrations for changes

**5. Clear Separation of Concerns**
- Controllers handle requests
- Services handle business logic
- Models handle data
- Views handle presentation

### Development Workflow

**Daily Workflow:**
1. Pull latest changes
2. Create feature branch
3. Implement feature
4. Test locally
5. Commit with clear message
6. Push and create PR

**Code Organization:**
- One feature per branch
- Small, focused commits
- Clear commit messages
- Regular pushes

### Maintenance Strategy

**Regular Tasks:**
- Weekly: Review and refactor code
- Bi-weekly: Update dependencies
- Monthly: Database optimization
- Quarterly: Security review

**Documentation:**
- README with setup instructions
- Code comments for complex logic
- API documentation (if needed)
- Deployment guide

### Scaling Considerations

**When to Scale:**
- User growth > 1000 users
- API costs become significant
- Performance issues appear
- Database queries slow down

**How to Scale:**
- Add caching (Redis)
- Optimize database queries
- Implement queue jobs
- Add CDN for assets

**Don't Scale Too Early:**
- Premature optimization is waste
- Build for current needs
- Scale when you have data
- Keep it simple until needed

---

## Security Considerations

### Authentication & Authorization

**Authentication:**
- Laravel's built-in auth
- Bcrypt password hashing
- Session-based auth
- CSRF protection

**Authorization:**
- Policy-based access control
- Users can only access their own data
- Middleware for route protection
- Input validation on all requests

### Data Protection

**Input Validation:**
- Validate all user inputs
- Sanitize text content
- Prevent SQL injection (Eloquent handles this)
- Prevent XSS (Blade handles this)

**API Security:**
- Rate limiting on AI endpoints
- API key protection (environment variables)
- Request throttling
- Error message sanitization

### Privacy

**User Data:**
- Store only necessary data
- Allow data deletion
- Clear privacy policy
- GDPR considerations (if applicable)

---

## Performance Optimization

### Database Optimization

**Indexing:**
- Primary keys (automatic)
- Foreign keys
- Search fields (title, created_at)
- Frequently queried fields

**Query Optimization:**
- Eager loading relationships
- Avoid N+1 queries
- Use pagination
- Cache frequent queries

### Frontend Optimization

**Asset Optimization:**
- Vite for bundling
- CSS/JS minification
- Image optimization
- Lazy loading

**Caching:**
- Browser caching
- CDN for static assets
- Cache API responses (future)

### API Optimization

**AI API Calls:**
- Cache generated content
- Queue long-running requests
- Implement retry logic
- Monitor API usage

---

## Deployment Strategy

### Development Environment

**Local Setup:**
- XAMPP (as specified)
- Laravel Valet (alternative)
- MySQL local database
- .env configuration

**Development Tools:**
- Laravel Debugbar
- Laravel Telescope (optional)
- PHPUnit for testing
- Laravel Pint for code style

### Production Environment

**Hosting Options:**
- Shared hosting (budget)
- VPS (DigitalOcean, Linode)
- PaaS (Laravel Forge, Vapor)

**Production Checklist:**
- [ ] Environment variables configured
- [ ] Database optimized
- [ ] Cache enabled
- [ ] Queue worker running
- [ ] SSL certificate installed
- [ ] Backup system in place
- [ ] Monitoring configured

---

## Summary

### Architecture Highlights

**Frontend:**
- Blade templates for server-side rendering
- Tailwind CSS for styling
- Alpine.js for interactivity
- Vite for asset bundling

**Backend:**
- Laravel 10+ framework
- MVC architecture
- Service layer for business logic
- Eloquent ORM for database

**Database:**
- MySQL 8.0+
- Normalized structure
- JSON for flexible content
- Proper indexing

**External:**
- OpenAI/Claude API for AI generation
- Rate limiting and error handling
- Queue system for long requests

### Key Decisions

1. **Blade over SPA** — Simpler, faster to build, easier to maintain
2. **Service Layer** — Clean separation, reusable logic
3. **JSON Storage** — Flexible for MVP, can migrate later
4. **Simple Auth** — Laravel built-in, secure by default
5. **Minimal Dependencies** — Keep it lean and manageable

### Next Steps

With architecture defined, we move to:

**Phase 4:** Design database schema (detailed table definitions)  
**Phase 5:** Map user flows (detailed user journeys)  
**Phase 6:** Plan UI/UX design (page layouts and components)  

Each phase builds on this architectural foundation.

---

*Document Version: 1.0*  
*Last Updated: 2026-03-25*  
*Status: Phase 3 Complete*
