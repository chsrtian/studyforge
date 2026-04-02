# StudyForge — AI Study Companion
## Phase 8: AI Output Strategy

---

## Executive Summary

This document defines how StudyForge's AI should behave for each output type, ensuring consistent, high-quality, and student-friendly results. The strategy focuses on creating outputs that are genuinely useful for studying, not just generic AI responses.

**Key Principles:**
- Student-focused outputs
- Consistent quality
- Appropriate detail level
- Clear, structured formatting
- Actionable study materials

---

## AI Service Configuration

### Recommended AI Models

**Primary Choice: OpenAI GPT-4**
- Best instruction following
- Excellent JSON output
- Reliable and consistent
- Good for educational content

**Alternative: Claude 3 Sonnet**
- Strong reasoning
- Good at structured outputs
- Slightly cheaper
- Good for complex explanations

**Budget Option: GPT-3.5 Turbo**
- Faster and cheaper
- Good for simple tasks
- May need more careful prompting
- Suitable for MVP

### Configuration Settings

```php
// config/services.php
'openai' => [
    'key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4'),
    'max_tokens' => env('OPENAI_MAX_TOKENS', 4000),
    'temperature' => env('OPENAI_TEMPERATURE', 0.3),
],
```

**Temperature Settings:**
- Summaries: 0.3 (factual, consistent)
- Flashcards: 0.4 (slightly creative)
- Quizzes: 0.3 (factual, accurate)
- Explanations: 0.5 (creative, engaging)

### Rate Limiting

**Per User Limits:**
- 10 generations per hour
- 50 generations per day
- 1000 generations per month

**Implementation:**
```php
// Using Laravel's built-in rate limiting
RateLimiter::for('ai-generation', function ($user) {
    return Limit::perHour(10)->by($user->id);
});
```

---

## 1. Summary Generation

### Purpose
Provide students with a concise, accurate overview of their study material, highlighting key concepts and main points.

### Good Output Characteristics

**Structure:**
```
Summary:
[3-5 paragraphs summarizing the main content]

Key Points:
• [Point 1]
• [Point 2]
• [Point 3]
• [Point 4]
• [Point 5]
```

**Quality Indicators:**
- ✅ Captures main ideas accurately
- ✅ Omits unnecessary details
- ✅ Uses clear, simple language
- ✅ Maintains logical flow
- ✅ Highlights important concepts
- ✅ Appropriate length (250-400 words)
- ✅ No hallucinated information
- ✅ Preserves factual accuracy

**Bad Output Examples:**
- ❌ Too short (under 100 words) — misses important details
- ❌ Too long (over 600 words) — defeats purpose of summary
- ❌ Inaccurate information — hallucinated facts
- ❌ Poor structure — no clear organization
- ❌ Too technical — uses jargon without explanation
- ❌ Too vague — doesn't capture specific details

### Prompt Strategy

**System Prompt:**
```
You are a study assistant AI that creates concise, accurate summaries of educational content. Your summaries should:

1. Capture the main ideas and key concepts
2. Use clear, simple language appropriate for students
3. Maintain factual accuracy — never add information not in the original
4. Be concise but comprehensive (250-400 words)
5. Include a "Key Points" section with 5-7 bullet points
6. Use proper academic tone but remain accessible
7. Preserve important details, examples, and definitions
8. Organize information logically

Format your response as:
Summary:
[3-5 paragraphs]

Key Points:
• [Point 1]
• [Point 2]
• [Point 3]
• [Point 4]
• [Point 5]
```

**User Prompt:**
```
Please summarize the following study material:

{input_text}
```

### Output Validation

**Checks:**
- Length: 250-400 words
- Contains "Summary:" header
- Contains "Key Points:" section
- Has 3-7 bullet points
- No placeholder text
- No obvious hallucinations

### User Controls

**Optional Parameters:**
- Length: Short (150-250 words), Medium (250-400 words), Long (400-600 words)
- Detail Level: High-level overview, Detailed summary

**Implementation:**
```php
$lengthMap = [
    'short' => '150-250 words',
    'medium' => '250-400 words',
    'long' => '400-600 words',
];

$detailMap = [
    'overview' => 'Focus on main ideas only',
    'detailed' => 'Include important details and examples',
];
```

---

## 2. Simplified Explanation

### Purpose
Break down complex concepts into easy-to-understand explanations, making difficult material accessible to all students.

### Good Output Characteristics

**Structure:**
```
Simple Explanation:
[2-3 paragraphs explaining the concept in simple terms]

Analogy:
[Optional: A relatable analogy to help understanding]

Why It Matters:
[1-2 sentences explaining importance]
```

**Quality Indicators:**
- ✅ Uses simple, everyday language
- ✅ Avoids jargon or explains it immediately
- ✅ Provides relatable analogies
- ✅ Builds from simple to complex
- ✅ Addresses common misconceptions
- ✅ Appropriate for target audience
- ✅ Engaging and interesting
- ✅ Accurate despite simplification

**Bad Output Examples:**
- ❌ Too simplistic — loses important nuance
- ❌ Still too complex — doesn't simplify enough
- ❌ Inaccurate analogies — misleading comparisons
- ❌ Condescending tone — talks down to students
- ❌ No structure — hard to follow

### Prompt Strategy

**System Prompt:**
```
You are a study assistant AI that explains complex concepts in simple, easy-to-understand terms. Your explanations should:

1. Use everyday language a high school student would understand
2. Avoid technical jargon or explain it immediately when used
3. Build from simple to complex ideas
4. Provide relatable analogies when helpful
5. Address common misconceptions
6. Be engaging and interesting
7. Maintain accuracy while simplifying
8. Be concise (150-300 words)

Format your response as:
Simple Explanation:
[2-3 paragraphs]

Analogy:
[Optional: A relatable analogy]

Why It Matters:
[1-2 sentences on importance]
```

**User Prompt:**
```
Please explain the following concept in simple terms:

{concept_or_text}
```

### Output Validation

**Checks:**
- Length: 150-300 words
- Contains "Simple Explanation:" header
- Uses simple vocabulary
- No unexplained jargon
- Includes analogy if appropriate

### User Controls

**Optional Parameters:**
- Complexity Level: Beginner, Intermediate, Advanced
- Include Analogies: Yes, No

**Implementation:**
```php
$complexityMap = [
    'beginner' => 'Explain as if to a high school student',
    'intermediate' => 'Explain as if to a college freshman',
    'advanced' => 'Explain as if to an advanced student',
];
```

---

## 3. Flashcard Generation

### Purpose
Create effective Q&A flashcards that promote active recall and help students memorize key information.

### Good Output Characteristics

**Structure:**
```json
{
  "cards": [
    {
      "question": "What is photosynthesis?",
      "answer": "The process by which plants convert sunlight, water, and carbon dioxide into glucose and oxygen."
    },
    {
      "question": "What are the two main stages of photosynthesis?",
      "answer": "Light-dependent reactions and light-independent reactions (Calvin cycle)."
    }
  ],
  "total_cards": 15
}
```

**Quality Indicators:**
- ✅ Questions are clear and specific
- ✅ Answers are concise but complete
- ✅ Covers main concepts from the material
- ✅ Appropriate difficulty level
- ✅ Mix of factual and conceptual questions
- ✅ No ambiguous questions
- ✅ Answers are accurate
- ✅ Good variety of question types

**Bad Output Examples:**
- ❌ Too vague — "What is important about X?"
- ❌ Too complex — multi-part questions
- ❌ Trivial questions — obvious answers
- ❌ Inaccurate answers — wrong information
- ❌ Too many cards — overwhelming (over 25)
- ❌ Too few cards — not useful (under 8)

### Prompt Strategy

**System Prompt:**
```
You are a study assistant AI that creates effective flashcards for active recall study. Your flashcards should:

1. Focus on key concepts, definitions, and important facts
2. Have clear, specific questions
3. Provide concise but complete answers
4. Cover the main topics in the material
5. Include a mix of:
   - Definition questions
   - Conceptual questions
   - Factual questions
   - Application questions (when appropriate)
6. Be appropriate for college/high school level
7. Avoid ambiguous or overly complex questions
8. Generate 10-20 cards depending on content length

Format your response as JSON:
{
  "cards": [
    {
      "question": "Clear, specific question",
      "answer": "Concise, complete answer"
    }
  ],
  "total_cards": [number]
}

Rules:
- Each question should test ONE concept
- Answers should be 1-3 sentences
- Questions should be self-contained (no "according to the text")
- Avoid yes/no questions
- Make questions that require recall, not just recognition
```

**User Prompt:**
```
Create flashcards from the following study material:

{input_text}
```

### Output Validation

**Checks:**
- Valid JSON format
- Contains "cards" array
- Each card has "question" and "answer"
- 10-20 cards total
- Questions are clear
- Answers are concise
- No placeholder text

### Quality Scoring

**Automated Quality Check:**
```php
function scoreFlashcardQuality($card) {
    $score = 0;
    
    // Question length (10-100 chars ideal)
    $qLen = strlen($card['question']);
    if ($qLen >= 10 && $qLen <= 100) $score += 2;
    
    // Answer length (20-200 chars ideal)
    $aLen = strlen($card['answer']);
    if ($aLen >= 20 && $aLen <= 200) $score += 2;
    
    // Question ends with question mark
    if (substr($card['question'], -1) === '?') $score += 1;
    
    // Answer doesn't start with "Yes" or "No"
    if (!preg_match('/^(yes|no)/i', $card['answer'])) $score += 1;
    
    return $score; // Max 6
}
```

### User Controls

**Optional Parameters:**
- Number of Cards: 10, 15, 20
- Difficulty: Easy, Medium, Hard
- Question Types: Definitions, Concepts, Facts, Mixed

**Implementation:**
```php
$difficultyMap = [
    'easy' => 'Focus on basic definitions and facts',
    'medium' => 'Mix of definitions, concepts, and applications',
    'hard' => 'Focus on complex concepts and applications',
];
```

---

## 4. Multiple Choice Quiz Generation

### Purpose
Create practice quizzes that test student knowledge with clear questions, plausible distractors, and helpful explanations.

### Good Output Characteristics

**Structure:**
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
      "explanation": "Mitochondria are known as the powerhouse because they produce ATP, the cell's energy currency."
    }
  ],
  "total_questions": 10
}
```

**Quality Indicators:**
- ✅ Questions are clear and unambiguous
- ✅ All options are plausible
- ✅ Only one correct answer
- ✅ Distractors are common misconceptions
- ✅ Explanations are helpful
- ✅ Covers main topics
- ✅ Appropriate difficulty
- ✅ No trick questions
- ✅ Grammatically consistent options

**Bad Output Examples:**
- ❌ Multiple correct answers
- ❌ Obviously wrong distractors
- ❌ Ambiguous questions
- ❌ No explanations
- ❌ Too easy or too hard
- ❌ Grammatically inconsistent options

### Prompt Strategy

**System Prompt:**
```
You are a study assistant AI that creates effective multiple-choice quizzes. Your quizzes should:

1. Test understanding of key concepts
2. Have clear, unambiguous questions
3. Include 4 options (A, B, C, D)
4. Have only ONE correct answer
5. Make distractors plausible but clearly wrong
6. Use common misconceptions as distractors
7. Provide helpful explanations for correct answers
8. Cover the main topics in the material
9. Include a mix of difficulty levels
10. Generate 10-15 questions

Format your response as JSON:
{
  "questions": [
    {
      "question": "Clear question",
      "options": {
        "A": "Option 1",
        "B": "Option 2",
        "C": "Option 3",
        "D": "Option 4"
      },
      "correct": "B",
      "explanation": "Why this answer is correct"
    }
  ],
  "total_questions": [number]
}

Rules:
- Questions should test understanding, not just recall
- Distractors should be common mistakes or misconceptions
- All options should be similar in length and structure
- Explanations should help students learn from mistakes
- Avoid "all of the above" or "none of the above"
- Questions should be self-contained
```

**User Prompt:**
```
Create a multiple-choice quiz from the following study material:

{input_text}
```

### Output Validation

**Checks:**
- Valid JSON format
- Contains "questions" array
- Each question has "question", "options", "correct", "explanation"
- Exactly 4 options per question
- Correct answer is A, B, C, or D
- 10-15 questions total
- No duplicate questions

### Distractor Quality

**Good Distractors:**
- Related but incorrect concepts
- Common misconceptions
- Partially correct statements
- Similar but wrong definitions

**Bad Distractors:**
- Obviously wrong
- Unrelated to topic
- Too similar to correct answer
- Grammatically different from other options

### User Controls

**Optional Parameters:**
- Number of Questions: 10, 15, 20
- Difficulty: Easy, Medium, Hard
- Include Explanations: Yes, No

**Implementation:**
```php
$difficultyMap = [
    'easy' => 'Focus on basic facts and definitions',
    'medium' => 'Mix of facts, concepts, and applications',
    'hard' => 'Focus on complex concepts and critical thinking',
];
```

---

## 5. True/False Generation

### Purpose
Create true/false questions that test factual knowledge and conceptual understanding.

### Good Output Characteristics

**Structure:**
```json
{
  "questions": [
    {
      "statement": "Mitochondria are found in all eukaryotic cells.",
      "answer": true,
      "explanation": "All eukaryotic cells contain mitochondria for energy production."
    },
    {
      "statement": "Photosynthesis only occurs in plants.",
      "answer": false,
      "explanation": "Some bacteria and algae also perform photosynthesis."
    }
  ],
  "total_questions": 10
}
```

**Quality Indicators:**
- ✅ Statements are clear and unambiguous
- ✅ 50/50 split of true and false
- ✅ False statements are plausible
- ✅ Explanations clarify why true/false
- ✅ Tests important concepts
- ✅ No trick questions
- ✅ Appropriate difficulty

**Bad Output Examples:**
- ❌ Ambiguous statements
- ❌ Obviously true or false
- ❌ Too many true or false
- ❌ No explanations
- ❌ Trivial facts

### Prompt Strategy

**System Prompt:**
```
You are a study assistant AI that creates effective true/false questions. Your questions should:

1. Test factual knowledge and conceptual understanding
2. Have clear, unambiguous statements
3. Include approximately 50% true and 50% false
4. Make false statements plausible but clearly false
5. Provide explanations for why each is true or false
6. Cover important concepts from the material
7. Avoid trick questions or ambiguous statements
8. Generate 10-15 questions

Format your response as JSON:
{
  "questions": [
    {
      "statement": "Clear statement",
      "answer": true,
      "explanation": "Why this is true or false"
    }
  ],
  "total_questions": [number]
}

Rules:
- Statements should be definitive (not "might" or "could")
- False statements should be common misconceptions
- Explanations should help students understand
- Avoid double negatives
- Keep statements concise (1-2 sentences)
```

**User Prompt:**
```
Create true/false questions from the following study material:

{input_text}
```

### Output Validation

**Checks:**
- Valid JSON format
- Contains "questions" array
- Each question has "statement", "answer", "explanation"
- Answer is boolean (true/false)
- 10-15 questions total
- Approximately 50/50 split

### User Controls

**Optional Parameters:**
- Number of Questions: 10, 15, 20
- Difficulty: Easy, Medium, Hard

---

## 6. Short Answer Question Generation

### Purpose
Create open-ended questions that require students to demonstrate understanding through written responses.

### Good Output Characteristics

**Structure:**
```json
{
  "questions": [
    {
      "question": "Explain the process of photosynthesis and its importance to life on Earth.",
      "key_points": ["sunlight", "water", "carbon dioxide", "glucose", "oxygen"],
      "sample_answer": "Photosynthesis is the process by which plants convert sunlight, water, and carbon dioxide into glucose and oxygen. This process is crucial because it produces the oxygen we breathe and the food that sustains most life on Earth.",
      "difficulty": "medium"
    }
  ],
  "total_questions": 8
}
```

**Quality Indicators:**
- ✅ Questions require explanation, not just recall
- ✅ Key points guide grading
- ✅ Sample answers demonstrate expected depth
- ✅ Appropriate difficulty level
- ✅ Tests understanding, not just memorization
- ✅ Clear and specific questions

**Bad Output Examples:**
- ❌ Yes/no questions
- ❌ Questions requiring only one word
- ❌ No key points provided
- ❌ No sample answers
- ❌ Too vague or too specific

### Prompt Strategy

**System Prompt:**
```
You are a study assistant AI that creates effective short-answer questions. Your questions should:

1. Require students to explain concepts in their own words
2. Test understanding, not just memorization
3. Include key points for grading
4. Provide sample answers for reference
5. Cover main topics from the material
6. Include a mix of difficulty levels
7. Generate 8-12 questions

Format your response as JSON:
{
  "questions": [
    {
      "question": "Question requiring explanation",
      "key_points": ["point1", "point2", "point3"],
      "sample_answer": "Example of a good answer",
      "difficulty": "medium"
    }
  ],
  "total_questions": [number]
}

Rules:
- Questions should require 2-4 sentence answers
- Key points should be the main concepts to cover
- Sample answers should be 3-5 sentences
- Avoid questions with single-word answers
- Focus on "explain", "describe", "compare", "analyze"
```

**User Prompt:**
```
Create short-answer questions from the following study material:

{input_text}
```

### Output Validation

**Checks:**
- Valid JSON format
- Contains "questions" array
- Each question has "question", "key_points", "sample_answer", "difficulty"
- Key points is an array
- 8-12 questions total

### User Controls

**Optional Parameters:**
- Number of Questions: 8, 10, 12
- Difficulty: Easy, Medium, Hard
- Include Sample Answers: Yes, No

---

## 7. Key Terms Extraction

### Purpose
Identify and define important terms and concepts from study material, helping students build vocabulary and understanding.

### Good Output Characteristics

**Structure:**
```json
{
  "terms": [
    {
      "term": "Photosynthesis",
      "definition": "The process by which plants convert sunlight, water, and carbon dioxide into glucose and oxygen.",
      "importance": "high",
      "example": "Plants use photosynthesis to create food for energy."
    }
  ],
  "total_terms": 15
}
```

**Quality Indicators:**
- ✅ Terms are important to the topic
- ✅ Definitions are clear and accurate
- ✅ Definitions are concise (1-2 sentences)
- ✅ Examples provided when helpful
- ✅ Importance levels assigned
- ✅ No trivial terms
- ✅ Definitions are student-friendly

**Bad Output Examples:**
- ❌ Trivial terms
- ❌ Inaccurate definitions
- ❌ Too long definitions
- ❌ No examples
- ❌ Too many terms (over 25)
- ❌ Too few terms (under 8)

### Prompt Strategy

**System Prompt:**
```
You are a study assistant AI that extracts and defines important terms from educational content. Your extraction should:

1. Identify key terms and concepts
2. Provide clear, accurate definitions
3. Keep definitions concise (1-2 sentences)
4. Include examples when helpful
5. Assign importance levels (high, medium, low)
6. Focus on terms essential to understanding the topic
7. Avoid overly technical or trivial terms
8. Generate 10-20 terms depending on content

Format your response as JSON:
{
  "terms": [
    {
      "term": "Important Term",
      "definition": "Clear, concise definition",
      "importance": "high",
      "example": "Optional example"
    }
  ],
  "total_terms": [number]
}

Rules:
- Terms should be essential to understanding the topic
- Definitions should be student-friendly
- Examples should illustrate usage
- Importance based on frequency and relevance
- Avoid jargon in definitions
```

**User Prompt:**
```
Extract and define important terms from the following study material:

{input_text}
```

### Output Validation

**Checks:**
- Valid JSON format
- Contains "terms" array
- Each term has "term", "definition", "importance"
- 10-20 terms total
- Definitions are concise
- Importance is high/medium/low

### User Controls

**Optional Parameters:**
- Number of Terms: 10, 15, 20
- Include Examples: Yes, No
- Importance Filter: All, High Only, High+Medium

---

## 8. Study Guide Generation

### Purpose
Create comprehensive study guides that organize content into structured, review-ready formats.

### Good Output Characteristics

**Structure:**
```
# Study Guide: [Topic]

## Overview
[Brief overview of the topic]

## Key Concepts
### 1. [Concept 1]
[Explanation]

### 2. [Concept 2]
[Explanation]

## Important Terms
- **Term 1**: Definition
- **Term 2**: Definition

## Main Points
1. [Point 1]
2. [Point 2]
3. [Point 3]

## Common Questions
**Q: [Question 1]**
A: [Answer]

**Q: [Question 2]**
A: [Answer]

## Study Tips
- [Tip 1]
- [Tip 2]
```

**Quality Indicators:**
- ✅ Well-organized structure
- ✅ Covers all main topics
- ✅ Clear headings and sections
- ✅ Concise but comprehensive
- ✅ Includes key terms
- ✅ Provides study tips
- ✅ Appropriate length (800-1500 words)

**Bad Output Examples:**
- ❌ Poor organization
- ❌ Missing important topics
- ❌ Too brief (under 500 words)
- ❌ Too long (over 2000 words)
- ❌ No clear structure

### Prompt Strategy

**System Prompt:**
```
You are a study assistant AI that creates comprehensive study guides from educational content. Your study guides should:

1. Organize content into clear sections
2. Cover all main topics and concepts
3. Include important terms and definitions
4. Provide key points for each section
5. Include common questions and answers
6. Offer study tips
7. Be comprehensive but concise (800-1500 words)
8. Use clear headings and formatting

Format your response as:
# Study Guide: [Topic]

## Overview
[Brief overview]

## Key Concepts
### 1. [Concept]
[Explanation]

## Important Terms
- **Term**: Definition

## Main Points
1. [Point]

## Common Questions
**Q: [Question]**
A: [Answer]

## Study Tips
- [Tip]
```

**User Prompt:**
```
Create a comprehensive study guide from the following study material:

{input_text}
```

### Output Validation

**Checks:**
- Contains "Study Guide:" header
- Has required sections (Overview, Key Concepts, Terms, Main Points, Questions, Tips)
- Length: 800-1500 words
- Clear formatting
- No placeholder text

### User Controls

**Optional Parameters:**
- Length: Short (500-800 words), Medium (800-1500 words), Long (1500-2500 words)
- Include Study Tips: Yes, No
- Include Common Questions: Yes, No

---

## AI Output Quality Assurance

### Quality Metrics

**Accuracy Score:**
- Factual accuracy compared to source
- No hallucinated information
- Preserves important details

**Usefulness Score:**
- Appropriate detail level
- Clear and understandable
- Actionable for studying

**Structure Score:**
- Proper formatting
- Clear organization
- Consistent style

**Engagement Score:**
- Student-friendly language
- Interesting presentation
- Encouraging tone

### Quality Control Process

**Step 1: Generation**
- AI generates output based on prompt
- Follows defined structure

**Step 2: Validation**
- Check format and structure
- Validate JSON (if applicable)
- Check length requirements
- Verify no placeholder text

**Step 3: Scoring**
- Calculate quality scores
- Flag low-quality outputs
- Log for improvement

**Step 4: Delivery**
- Return to user
- Show quality indicators
- Provide feedback option

### Quality Improvement

**Feedback Loop:**
- Users can rate outputs
- Track quality metrics
- Identify common issues
- Improve prompts over time

**A/B Testing:**
- Test different prompts
- Compare quality scores
- Optimize for best results
- Iterate on improvements

---

## Prompt Engineering Best Practices

### General Principles

**Be Specific:**
- Clear instructions
- Explicit format requirements
- Specific quality criteria
- Example outputs

**Be Structured:**
- Use numbered lists
- Separate sections clearly
- Define expected format
- Provide templates

**Be Concise:**
- Avoid unnecessary words
- Focus on key requirements
- Remove ambiguity
- Use clear language

### Prompt Templates

**Template Structure:**
```
[Role Definition]

[Task Description]

[Quality Criteria]

[Format Requirements]

[Rules and Constraints]

[Example Output (optional)]
```

### Common Pitfalls

**Avoid:**
- Vague instructions
- Conflicting requirements
- Too many tasks at once
- Ambiguous format requests
- Missing quality criteria

---

## Rate Limiting and Cost Management

### Rate Limiting Strategy

**Per User Limits:**
- 10 generations per hour
- 50 generations per day
- 1000 generations per month

**Implementation:**
```php
class AiService
{
    public function generate($type, $input, $user)
    {
        // Check rate limits
        if (!$this->checkRateLimit($user)) {
            throw new RateLimitException('Rate limit exceeded');
        }
        
        // Generate content
        $output = $this->callAI($type, $input);
        
        // Update usage
        $this->updateUsage($user);
        
        return $output;
    }
}
```

### Cost Management

**Token Optimization:**
- Limit input length (50,000 chars max)
- Use appropriate max_tokens
- Cache frequent requests
- Use cheaper models for simple tasks

**Cost Tracking:**
```php
class AiService
{
    public function trackCost($tokens, $model)
    {
        $cost = $this->calculateCost($tokens, $model);
        
        Log::info('AI Cost', [
            'user_id' => auth()->id(),
            'tokens' => $tokens,
            'cost' => $cost,
            'model' => $model,
        ]);
    }
}
```

### Caching Strategy

**Cache Generated Content:**
- Hash input text
- Check cache before API call
- Store successful outputs
- Set appropriate TTL (24 hours)

**Implementation:**
```php
$cacheKey = 'ai_output_' . md5($input . $type);
$cached = Cache::get($cacheKey);

if ($cached) {
    return $cached;
}

$output = $this->callAI($type, $input);
Cache::put($cacheKey, $output, now()->addHours(24));

return $output;
```

---

## Error Handling

### Common Errors

**Rate Limit Exceeded:**
- Message: "You've reached the generation limit. Please try again later."
- Show retry time
- Log for monitoring

**API Timeout:**
- Message: "The AI service is taking longer than usual. Please try again."
- Retry automatically (1 attempt)
- Log for investigation

**Invalid Response:**
- Message: "Unable to generate study materials. Please try again."
- Log response for debugging
- Retry with simpler prompt

**Network Error:**
- Message: "Connection issue. Please check your internet and try again."
- Retry automatically (2 attempts)
- Log for monitoring

### Error Recovery

**Automatic Retry:**
- Retry up to 2 times
- Exponential backoff
- Log retry attempts

**Fallback Options:**
- Use simpler prompt
- Use different model
- Show cached content
- Provide manual workaround

---

## Summary

### Output Types

1. **Summary** — Concise overview with key points
2. **Simplified Explanation** — Easy-to-understand explanations
3. **Flashcards** — Q&A cards for active recall
4. **Multiple Choice Quiz** — Practice questions with distractors
5. **True/False** — Factual knowledge testing
6. **Short Answer** — Open-ended questions
7. **Key Terms** — Important vocabulary
8. **Study Guide** — Comprehensive review material

### Quality Standards

**All Outputs Must:**
- Be accurate and factual
- Be appropriate length
- Use clear formatting
- Be student-friendly
- Provide actionable value

### User Controls

**Available Options:**
- Length/Detail level
- Difficulty
- Number of items
- Question types
- Include/exclude features

### Implementation Priority

**MVP (Version 1.0):**
1. Summary
2. Flashcards
3. Multiple Choice Quiz
4. Key Terms

**Version 2.0:**
5. Simplified Explanation
6. True/False
7. Short Answer
8. Study Guide

### Next Steps

With AI output strategy defined, we move to:

**Phase 9:** Create development roadmap  
**Phase 10:** Begin implementation execution  

Each phase will reference these AI specifications for implementation.

---

*Document Version: 1.0*  
*Last Updated: 2026-03-25*  
*Status: Phase 8 Complete*
