# StudyForge — AI Study Companion
## Phase 4: Database Design

---

## Executive Summary

This document defines the complete database schema for StudyForge. The design follows Laravel conventions and best practices, ensuring data integrity, performance, and scalability while remaining simple enough for a student developer to manage.

**Design Principles:**
- Normalize where it makes sense
- Use Laravel's Eloquent conventions
- Plan for future features without over-engineering
- Optimize for common query patterns
- Maintain clear relationships

---

## Database Overview

### Technology
- **Database:** MySQL 8.0+
- **ORM:** Laravel Eloquent
- **Character Set:** utf8mb4
- **Collation:** utf8mb4_unicode_ci

### Tables Summary

| Table | Purpose | Records (Est.) |
|-------|---------|-----------------|
| **users** | User accounts | 100-10,000 |
| **study_sessions** | Study session metadata | 500-50,000 |
| **generated_outputs** | AI-generated content | 1,500-150,000 |
| **flashcards** | Individual flashcards | 5,000-500,000 |
| **quizzes** | Quiz metadata | 500-50,000 |
| **quiz_questions** | Individual quiz questions | 5,000-500,000 |
| **personal_access_tokens** | API authentication (Sanctum) | 100-10,000 |
| **password_reset_tokens** | Password reset | 0-1,000 |
| **sessions** | User sessions | 100-10,000 |

---

## Table Definitions

### 1. users

**Purpose:** Store user account information and authentication data

**Laravel Model:** `App\Models\User`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| `name` | varchar(255) | NOT NULL | User's full name |
| `email` | varchar(255) | NOT NULL, UNIQUE | User's email address |
| `email_verified_at` | timestamp | NULLABLE | Email verification timestamp |
| `password` | varchar(255) | NOT NULL | Hashed password (bcrypt) |
| `remember_token` | varchar(100) | NULLABLE | "Remember me" token |
| `created_at` | timestamp | NOT NULL | Account creation timestamp |
| `updated_at` | timestamp | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE INDEX on `email`
- INDEX on `created_at` (for sorting)

**Relationships:**
- Has many `study_sessions`
- Has many `generated_outputs` (through study_sessions)
- Has many `flashcards` (through study_sessions)
- Has many `quizzes` (through study_sessions)

**Notes:**
- Uses Laravel's built-in User model
- Password hashing handled by Laravel
- Email verification optional for MVP

---

### 2. study_sessions

**Purpose:** Store study session metadata and input content

**Laravel Model:** `App\Models\StudySession`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint | PRIMARY KEY, AUTO_INCREMENT | Unique session identifier |
| `user_id` | bigint | FOREIGN KEY, NOT NULL | Reference to users table |
| `title` | varchar(255) | NOT NULL | Session title (auto-generated or user-edited) |
| `input_text` | longtext | NOT NULL | Original study material text |
| `input_word_count` | int | NOT NULL, DEFAULT 0 | Word count of input text |
| `status` | enum | NOT NULL, DEFAULT 'completed' | Session status: pending, processing, completed, failed |
| `metadata` | json | NULLABLE | Additional metadata (language, content type, etc.) |
| `created_at` | timestamp | NOT NULL | Session creation timestamp |
| `updated_at` | timestamp | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `user_id` (for user's sessions)
- INDEX on `created_at` (for sorting)
- INDEX on `status` (for filtering)
- INDEX on `user_id, created_at` (composite for user's recent sessions)

**Relationships:**
- Belongs to `user`
- Has many `generated_outputs`
- Has many `flashcards`
- Has many `quizzes`

**Notes:**
- `input_text` stored as longtext to support large documents
- `title` auto-generated from first 50 characters of input
- `status` tracks generation progress
- `metadata` JSON for future flexibility (language detection, content type, etc.)

---

### 3. generated_outputs

**Purpose:** Store all AI-generated content (summaries, key terms, etc.)

**Laravel Model:** `App\Models\GeneratedOutput`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint | PRIMARY KEY, AUTO_INCREMENT | Unique output identifier |
| `study_session_id` | bigint | FOREIGN KEY, NOT NULL | Reference to study_sessions table |
| `type` | enum | NOT NULL | Output type: summary, key_terms, simplified_explanation |
| `content` | json | NOT NULL | Generated content in JSON format |
| `generation_time` | int | NULLABLE | Time taken to generate (seconds) |
| `ai_model` | varchar(100) | NULLABLE | AI model used (gpt-4, claude-3, etc.) |
| `tokens_used` | int | NULLABLE | Number of tokens consumed |
| `created_at` | timestamp | NOT NULL | Generation timestamp |
| `updated_at` | timestamp | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `study_session_id` (for session's outputs)
- INDEX on `type` (for filtering by type)
- INDEX on `study_session_id, type` (composite for specific output type)

**Relationships:**
- Belongs to `study_session`

**Notes:**
- `content` is JSON for flexibility
- Different output types have different JSON structures
- `generation_time` and `tokens_used` for monitoring and optimization
- `ai_model` tracks which model generated the content

**JSON Content Structures:**

**Summary Type:**
```json
{
  "summary": "Full summary text...",
  "key_points": ["Point 1", "Point 2", "Point 3"],
  "word_count": 250
}
```

**Key Terms Type:**
```json
{
  "terms": [
    {
      "term": "Photosynthesis",
      "definition": "The process by which plants convert sunlight into energy..."
    }
  ],
  "total_terms": 15
}
```

**Simplified Explanation Type:**
```json
{
  "explanation": "Simple explanation text...",
  "complexity_level": "beginner",
  "word_count": 150
}
```

---

### 4. flashcards

**Purpose:** Store individual flashcards for active recall study

**Laravel Model:** `App\Models\Flashcard`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint | PRIMARY KEY, AUTO_INCREMENT | Unique flashcard identifier |
| `study_session_id` | bigint | FOREIGN KEY, NOT NULL | Reference to study_sessions table |
| `question` | text | NOT NULL | Flashcard question (front) |
| `answer` | text | NOT NULL | Flashcard answer (back) |
| `order` | int | NOT NULL, DEFAULT 0 | Display order within session |
| `difficulty` | enum | NULLABLE | Difficulty level: easy, medium, hard |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `study_session_id` (for session's flashcards)
- INDEX on `study_session_id, order` (composite for ordered retrieval)

**Relationships:**
- Belongs to `study_session`

**Notes:**
- `order` field for consistent display order
- `difficulty` field for future spaced repetition features
- Individual records enable future features (user edits, performance tracking)

---

### 5. quizzes

**Purpose:** Store quiz metadata and configuration

**Laravel Model:** `App\Models\Quiz`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint | PRIMARY KEY, AUTO_INCREMENT | Unique quiz identifier |
| `study_session_id` | bigint | FOREIGN KEY, NOT NULL | Reference to study_sessions table |
| `title` | varchar(255) | NOT NULL | Quiz title |
| `description` | text | NULLABLE | Quiz description |
| `total_questions` | int | NOT NULL, DEFAULT 0 | Number of questions |
| `time_limit` | int | NULLABLE | Time limit in minutes (future feature) |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `study_session_id` (for session's quizzes)

**Relationships:**
- Belongs to `study_session`
- Has many `quiz_questions`

**Notes:**
- `time_limit` for future timed quiz feature
- `total_questions` denormalized for quick display
- Separate from questions for cleaner structure

---

### 6. quiz_questions

**Purpose:** Store individual quiz questions with options and answers

**Laravel Model:** `App\Models\QuizQuestion`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint | PRIMARY KEY, AUTO_INCREMENT | Unique question identifier |
| `quiz_id` | bigint | FOREIGN KEY, NOT NULL | Reference to quizzes table |
| `question` | text | NOT NULL | Question text |
| `options` | json | NOT NULL | Answer options (A, B, C, D) |
| `correct_answer` | varchar(1) | NOT NULL | Correct answer (A, B, C, or D) |
| `explanation` | text | NULLABLE | Explanation of correct answer |
| `order` | int | NOT NULL, DEFAULT 0 | Display order within quiz |
| `difficulty` | enum | NULLABLE | Difficulty level: easy, medium, hard |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `quiz_id` (for quiz's questions)
- INDEX on `quiz_id, order` (composite for ordered retrieval)

**Relationships:**
- Belongs to `quiz`

**Notes:**
- `options` stored as JSON for flexibility
- `correct_answer` stores just the letter (A, B, C, D)
- `explanation` helps students learn from mistakes
- `order` for consistent question sequence

**JSON Options Structure:**
```json
{
  "A": "Nucleus",
  "B": "Mitochondria",
  "C": "Ribosome",
  "D": "Golgi apparatus"
}
```

---

### 7. personal_access_tokens (Laravel Sanctum)

**Purpose:** API token authentication for future API features

**Laravel Model:** `Laravel\Sanctum\PersonalAccessToken`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | bigint | PRIMARY KEY, AUTO_INCREMENT | Unique token identifier |
| `tokenable_type` | varchar(255) | NOT NULL | Tokenable model type |
| `tokenable_id` | bigint | NOT NULL | Tokenable model ID |
| `name` | varchar(255) | NOT NULL | Token name |
| `token` | varchar(64) | NOT NULL, UNIQUE | Hashed token |
| `abilities` | text | NULLABLE | Token abilities |
| `last_used_at` | timestamp | NULLABLE | Last usage timestamp |
| `expires_at` | timestamp | NULLABLE | Expiration timestamp |
| `created_at` | timestamp | NOT NULL | Creation timestamp |
| `updated_at` | timestamp | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE INDEX on `token`
- INDEX on `tokenable_type, tokenable_id`

**Notes:**
- Laravel Sanctum built-in table
- For future API/mobile app features
- Not critical for MVP web application

---

### 8. password_reset_tokens

**Purpose:** Store password reset tokens

**Laravel Model:** (No model, used by built-in auth)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `email` | varchar(255) | PRIMARY KEY | User's email |
| `token` | varchar(255) | NOT NULL | Reset token |
| `created_at` | timestamp | NULLABLE | Token creation timestamp |

**Indexes:**
- PRIMARY KEY on `email`

**Notes:**
- Laravel built-in table for password resets
- Simple structure, no model needed

---

### 9. sessions

**Purpose:** Store user session data

**Laravel Model:** (No model, used by session driver)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | varchar(255) | PRIMARY KEY | Session ID |
| `user_id` | bigint | NULLABLE | User ID (if authenticated) |
| `ip_address` | varchar(45) | NULLABLE | User's IP address |
| `user_agent` | text | NULLABLE | User's browser agent |
| `payload` | longtext | NOT NULL | Session data |
| `last_activity` | int | NOT NULL | Last activity timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `last_activity`
- INDEX on `user_id`

**Notes:**
- Laravel built-in table for sessions
- Used for authentication sessions
- No model needed

---

## Entity Relationship Diagram

```
┌─────────────────┐
│     users       │
│─────────────────│
│ id (PK)         │
│ name            │
│ email (UNIQUE)  │
│ password        │
│ created_at      │
│ updated_at      │
└────────┬────────┘
         │
         │ 1:N
         │
┌────────▼────────┐
│ study_sessions  │
│─────────────────│
│ id (PK)         │
│ user_id (FK)    │
│ title           │
│ input_text      │
│ status          │
│ created_at      │
│ updated_at      │
└────────┬────────┘
         │
         │ 1:N
         ├──────────────────┬──────────────────┐
         │                  │                  │
┌────────▼────────┐  ┌─────▼─────┐  ┌────────▼────────┐
│generated_outputs│  │ flashcards │  │    quizzes      │
│─────────────────│  │───────────│  │─────────────────│
│ id (PK)         │  │ id (PK)   │  │ id (PK)         │
│ study_session_id│  │session_id │  │study_session_id │
│ type            │  │ question  │  │ title           │
│ content (JSON)  │  │ answer    │  │ total_questions │
│ created_at      │  │ order     │  │ created_at      │
└─────────────────┘  └───────────┘  └────────┬────────┘
                                              │
                                              │ 1:N
                                              │
                                       ┌──────▼────────┐
                                       │quiz_questions  │
                                       │───────────────│
                                       │ id (PK)       │
                                       │ quiz_id (FK)  │
                                       │ question      │
                                       │ options (JSON)│
                                       │ correct_answer│
                                       │ explanation   │
                                       │ order         │
                                       └───────────────┘
```

---

## Migrations

### Migration Files Structure

```
database/migrations/
├── 2024_01_01_000001_create_users_table.php
├── 2024_01_01_000002_create_study_sessions_table.php
├── 2024_01_01_000003_create_generated_outputs_table.php
├── 2024_01_01_000004_create_flashcards_table.php
├── 2024_01_01_000005_create_quizzes_table.php
├── 2024_01_01_000006_create_quiz_questions_table.php
├── 2024_01_01_000007_create_personal_access_tokens_table.php
└── 2024_01_01_000008_create_sessions_table.php
```

### Sample Migration: study_sessions

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->longText('input_text');
            $table->integer('input_word_count')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('completed');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('created_at');
            $table->index('status');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
    }
};
```

### Sample Migration: generated_outputs

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_session_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['summary', 'key_terms', 'simplified_explanation']);
            $table->json('content');
            $table->integer('generation_time')->nullable();
            $table->string('ai_model')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('study_session_id');
            $table->index('type');
            $table->index(['study_session_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_outputs');
    }
};
```

---

## Eloquent Models

### User Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }
}
```

### StudySession Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'input_text',
        'input_word_count',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'input_word_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatedOutputs(): HasMany
    {
        return $this->hasMany(GeneratedOutput::class);
    }

    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function getSummaryOutput()
    {
        return $this->generatedOutputs()
            ->where('type', 'summary')
            ->first();
    }

    public function getKeyTermsOutput()
    {
        return $this->generatedOutputs()
            ->where('type', 'key_terms')
            ->first();
    }
}
```

### GeneratedOutput Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_session_id',
        'type',
        'content',
        'generation_time',
        'ai_model',
        'tokens_used',
    ];

    protected $casts = [
        'content' => 'array',
        'generation_time' => 'integer',
        'tokens_used' => 'integer',
    ];

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }
}
```

### Flashcard Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flashcard extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_session_id',
        'question',
        'answer',
        'order',
        'difficulty',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }
}
```

### Quiz Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_session_id',
        'title',
        'description',
        'total_questions',
        'time_limit',
    ];

    protected $casts = [
        'total_questions' => 'integer',
        'time_limit' => 'integer',
    ];

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }
}
```

### QuizQuestion Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question',
        'options',
        'correct_answer',
        'explanation',
        'order',
        'difficulty',
    ];

    protected $casts = [
        'options' => 'array',
        'order' => 'integer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
```

---

## Query Patterns

### Common Queries

**Get user's recent sessions:**
```php
StudySession::where('user_id', auth()->id())
    ->orderBy('created_at', 'desc')
    ->paginate(20);
```

**Get session with all outputs:**
```php
StudySession::with(['generatedOutputs', 'flashcards', 'quizzes'])
    ->findOrFail($id);
```

**Get session's summary:**
```php
$session->generatedOutputs()
    ->where('type', 'summary')
    ->first();
```

**Get session's flashcards:**
```php
$session->flashcards()
    ->orderBy('order')
    ->get();
```

**Get quiz with questions:**
```php
Quiz::with('questions')
    ->findOrFail($id);
```

**Search sessions by title:**
```php
StudySession::where('user_id', auth()->id())
    ->where('title', 'like', "%{$search}%")
    ->orderBy('created_at', 'desc')
    ->get();
```

---

## Data Integrity

### Foreign Key Constraints

All foreign keys use `onDelete('cascade')` to maintain referential integrity:
- Deleting a user deletes all their sessions
- Deleting a session deletes all its outputs, flashcards, and quizzes
- Deleting a quiz deletes all its questions

### Validation Rules

**StudySession:**
- `user_id`: required, exists:users,id
- `title`: required, string, max:255
- `input_text`: required, string, min:100, max:50000
- `status`: required, in:pending,processing,completed,failed

**GeneratedOutput:**
- `study_session_id`: required, exists:study_sessions,id
- `type`: required, in:summary,key_terms,simplified_explanation
- `content`: required, json

**Flashcard:**
- `study_session_id`: required, exists:study_sessions,id
- `question`: required, string
- `answer`: required, string
- `order`: required, integer, min:0

**Quiz:**
- `study_session_id`: required, exists:study_sessions,id
- `title`: required, string, max:255
- `total_questions`: required, integer, min:1

**QuizQuestion:**
- `quiz_id`: required, exists:quizzes,id
- `question`: required, string
- `options`: required, json
- `correct_answer`: required, in:A,B,C,D
- `order`: required, integer, min:0

---

## Performance Optimization

### Indexing Strategy

**Primary Indexes:**
- All `id` fields (automatic)
- All foreign keys
- Frequently queried fields

**Composite Indexes:**
- `user_id, created_at` for user's recent sessions
- `study_session_id, type` for specific output types
- `quiz_id, order` for ordered questions

### Query Optimization

**Eager Loading:**
```php
// Good: Eager load relationships
StudySession::with(['generatedOutputs', 'flashcards'])->get();

// Bad: N+1 query problem
$sessions = StudySession::get();
foreach ($sessions as $session) {
    $session->generatedOutputs; // Additional query each time
}
```

**Pagination:**
```php
// Always paginate large result sets
StudySession::where('user_id', auth()->id())
    ->orderBy('created_at', 'desc')
    ->paginate(20);
```

**Select Only Needed Columns:**
```php
// Good: Select only needed columns
StudySession::select('id', 'title', 'created_at')->get();

// Bad: Select all columns
StudySession::get();
```

---

## Backup Strategy

### Regular Backups

**Daily Backups:**
- Full database dump
- Store in secure location
- Retain for 30 days

**Backup Command:**
```bash
mysqldump -u username -p studyforge > backup_$(date +%Y%m%d).sql
```

### Data Retention

**User Data:**
- Keep as long as account is active
- Delete on account deletion
- Allow data export before deletion

**Study Sessions:**
- Keep indefinitely while account is active
- Allow user to delete individual sessions
- Archive old sessions (future feature)

---

## Future Considerations

### Potential Additions (Version 2.0)

**User Preferences Table:**
```sql
user_preferences
├── id
├── user_id (FK)
├── preferred_ai_model
├── default_difficulty
├── theme_preference
└── notification_settings (JSON)
```

**Subjects/Categories Table:**
```sql
subjects
├── id
├── user_id (FK)
├── name
├── color
└── description

study_session_subject (pivot)
├── study_session_id
└── subject_id
```

**User Progress Table:**
```sql
user_progress
├── id
├── user_id (FK)
├── study_session_id (FK)
├── quiz_score
├── flashcards_reviewed
├── time_spent
└── completed_at
```

### Migration Path

When adding new features:
1. Create new migration
2. Add new columns/tables
3. Update models
4. Update controllers/services
5. Test thoroughly
6. Deploy

---

## Summary

### Database Design Highlights

**Core Tables:**
- `users` — User accounts
- `study_sessions` — Study session metadata
- `generated_outputs` — AI-generated content
- `flashcards` — Individual flashcards
- `quizzes` — Quiz metadata
- `quiz_questions` — Individual questions

**Key Design Decisions:**
1. **JSON for flexible content** — Allows different output structures
2. **Separate tables for flashcards/quizzes** — Enables future features
3. **Proper indexing** — Optimizes common queries
4. **Foreign key constraints** — Maintains data integrity
5. **Eloquent conventions** — Follows Laravel best practices

**Scalability:**
- Handles 10,000+ users
- Handles 50,000+ study sessions
- Handles 500,000+ flashcards/quizzes
- Optimized for common query patterns

**Maintainability:**
- Clear table purposes
- Consistent naming
- Well-documented
- Easy to extend

### Next Steps

With database design complete, we move to:

**Phase 5:** Map user flows (detailed user journeys)  
**Phase 6:** Plan UI/UX design (page layouts and components)  
**Phase 7:** Break down pages and modules  

Each phase will reference this database schema for data requirements.

---

*Document Version: 1.0*  
*Last Updated: 2026-03-25*  
*Status: Phase 4 Complete*
