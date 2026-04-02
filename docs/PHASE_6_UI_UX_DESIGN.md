# StudyForge — AI Study Companion
## Phase 6: UI/UX Design Plan

---

## Executive Summary

This document defines the visual design system and user experience patterns for StudyForge. The design prioritizes clarity, focus, and student motivation while maintaining a modern, professional aesthetic that builds trust.

**Design Philosophy:**
- Clean and uncluttered
- Student-focused and motivating
- Professional but approachable
- Accessible and readable
- Consistent across all pages

---

## 1. Design System Foundation

### Color Palette

**Primary Colors:**
```
Primary Blue: #3B82F6 (RGB: 59, 130, 246)
- Used for: CTAs, links, active states
- Conveys: Trust, reliability, focus

Primary Dark: #1E40AF (RGB: 30, 64, 175)
- Used for: Hover states, emphasis
- Conveys: Depth, professionalism
```

**Secondary Colors:**
```
Accent Purple: #8B5CF6 (RGB: 139, 92, 246)
- Used for: Highlights, achievements
- Conveys: Creativity, motivation

Success Green: #10B981 (RGB: 16, 185, 129)
- Used for: Success messages, correct answers
- Conveys: Achievement, progress

Warning Orange: #F59E0B (RGB: 245, 158, 11)
- Used for: Warnings, attention
- Conveys: Caution, importance

Error Red: #EF4444 (RGB: 239, 68, 68)
- Used for: Errors, incorrect answers
- Conveys: Urgency, mistakes
```

**Neutral Colors:**
```
Gray 50: #F9FAFB (RGB: 249, 250, 251)
- Used for: Backgrounds, cards

Gray 100: #F3F4F6 (RGB: 243, 244, 246)
- Used for: Borders, dividers

Gray 200: #E5E7EB (RGB: 229, 231, 235)
- Used for: Disabled states

Gray 600: #4B5563 (RGB: 75, 85, 99)
- Used for: Secondary text

Gray 800: #1F2937 (RGB: 31, 41, 55)
- Used for: Primary text

Gray 900: #111827 (RGB: 17, 24, 39)
- Used for: Headings, emphasis
```

### Typography

**Font Family:**
```
Primary: Inter (Google Fonts)
Fallback: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif
```

**Font Sizes:**
```
Display: 48px (3rem) — Hero sections, landing page
Heading 1: 36px (2.25rem) — Page titles
Heading 2: 30px (1.875rem) — Section titles
Heading 3: 24px (1.5rem) — Card titles
Heading 4: 20px (1.25rem) — Subsections
Body Large: 18px (1.125rem) — Important text
Body: 16px (1rem) — Regular text
Body Small: 14px (0.875rem) — Secondary text
Caption: 12px (0.75rem) — Labels, metadata
```

**Font Weights:**
```
Regular: 400 — Body text
Medium: 500 — Emphasis
Semibold: 600 — Headings
Bold: 700 — Strong emphasis
```

**Line Heights:**
```
Tight: 1.25 — Headings
Normal: 1.5 — Body text
Relaxed: 1.75 — Long-form content
```

### Spacing System

**Base Unit:** 4px

**Spacing Scale:**
```
0: 0px
1: 4px
2: 8px
3: 12px
4: 16px
5: 20px
6: 24px
8: 32px
10: 40px
12: 48px
16: 64px
20: 80px
24: 96px
```

**Common Spacing:**
- Small gaps: 8px (spacing-2)
- Medium gaps: 16px (spacing-4)
- Large gaps: 24px (spacing-6)
- Section spacing: 32px (spacing-8)
- Page padding: 24px (spacing-6)

### Border Radius

```
None: 0px
Small: 4px — Buttons, inputs
Medium: 8px — Cards, containers
Large: 12px — Modals, large containers
Full: 9999px — Pills, avatars
```

### Shadows

```
Small: 0 1px 2px 0 rgba(0, 0, 0, 0.05)
Medium: 0 4px 6px -1px rgba(0, 0, 0, 0.1)
Large: 0 10px 15px -3px rgba(0, 0, 0, 0.1)
Extra Large: 0 20px 25px -5px rgba(0, 0, 0, 0.1)
```

---

## 2. Page Structure

### Layout Strategy

**Main Layout (Authenticated):**
```
┌─────────────────────────────────────────────────────────┐
│                    Top Navigation Bar                    │
│  Logo    Dashboard    New Session    History    Profile  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│                    Main Content Area                     │
│                    (Scrollable)                         │
│                                                         │
│                                                         │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

**Alternative Layout (Study Mode):**
```
┌─────────────────────────────────────────────────────────┐
│                    Minimal Top Bar                      │
│  Logo    Session Title              Back    Profile     │
├─────────────────────────────────────────────────────────┤
│                                                         │
│                    Study Content Area                    │
│                    (Focused, No Distractions)           │
│                                                         │
│                                                         │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Responsive Breakpoints

```
Mobile: < 640px
Tablet: 640px - 1024px
Desktop: > 1024px
Large Desktop: > 1280px
```

---

## 3. Component Design

### Buttons

**Primary Button:**
```
Style:
- Background: Primary Blue (#3B82F6)
- Text: White
- Padding: 12px 24px
- Border Radius: 6px
- Font Weight: 600
- Hover: Primary Dark (#1E40AF)
- Active: Primary Dark (#1E40AF)
- Disabled: Gray 200 (#E5E7EB)

States:
- Default: Primary Blue
- Hover: Primary Dark
- Active: Primary Dark
- Disabled: Gray 200, cursor not-allowed
- Loading: Spinner icon, text hidden
```

**Secondary Button:**
```
Style:
- Background: White
- Border: 2px solid Gray 200 (#E5E7EB)
- Text: Gray 800 (#1F2937)
- Padding: 12px 24px
- Border Radius: 6px
- Font Weight: 600
- Hover: Gray 100 (#F3F4F6)
- Active: Gray 100 (#F3F4F6)

States:
- Default: White background
- Hover: Gray 100 background
- Active: Gray 100 background
- Disabled: Gray 200 background, cursor not-allowed
```

**Ghost Button:**
```
Style:
- Background: Transparent
- Text: Primary Blue (#3B82F6)
- Padding: 12px 24px
- Border Radius: 6px
- Font Weight: 600
- Hover: Gray 50 (#F9FAFB)
- Active: Gray 50 (#F9FAFB)

States:
- Default: Transparent background
- Hover: Gray 50 background
- Active: Gray 50 background
```

**Icon Button:**
```
Style:
- Background: Transparent
- Icon: Gray 600 (#4B5563)
- Padding: 8px
- Border Radius: 6px
- Hover: Gray 100 (#F3F4F6)
- Active: Gray 100 (#F3F4F6)

States:
- Default: Transparent background
- Hover: Gray 100 background
- Active: Gray 100 background
```

### Cards

**Standard Card:**
```
Style:
- Background: White
- Border: 1px solid Gray 100 (#F3F4F6)
- Border Radius: 8px
- Padding: 24px
- Shadow: Small (0 1px 2px 0 rgba(0, 0, 0, 0.05))
- Hover: Shadow Medium (0 4px 6px -1px rgba(0, 0, 0, 0.1))

Content:
- Title: Heading 3, Gray 900
- Description: Body, Gray 600
- Metadata: Caption, Gray 600
- Actions: Buttons or links
```

**Interactive Card:**
```
Style:
- Same as Standard Card
- Hover: Border Primary Blue, Shadow Medium
- Active: Border Primary Blue, Shadow Large
- Cursor: pointer

Behavior:
- Click anywhere to navigate
- Or click specific action buttons
```

**Study Card (Flashcard):**
```
Style:
- Background: White
- Border: 2px solid Gray 100 (#F3F4F6)
- Border Radius: 12px
- Padding: 32px
- Shadow: Medium (0 4px 6px -1px rgba(0, 0, 0, 0.1))
- Min Height: 300px
- Display: Flex, centered content

Content:
- Question/Answer: Heading 3, Gray 900, centered
- Font Size: 20px
- Line Height: 1.6
```

### Forms

**Text Input:**
```
Style:
- Background: White
- Border: 2px solid Gray 200 (#E5E7EB)
- Border Radius: 6px
- Padding: 12px 16px
- Font Size: 16px
- Text Color: Gray 900
- Placeholder: Gray 400

States:
- Default: Gray 200 border
- Focus: Primary Blue border, ring 2px Primary Blue with 20% opacity
- Error: Error Red border
- Disabled: Gray 100 background, Gray 400 text
```

**Textarea:**
```
Style:
- Same as Text Input
- Min Height: 200px
- Resize: Vertical
- Line Height: 1.6

Features:
- Character counter (bottom right)
- Auto-resize (optional)
- Clear button (top right)
```

**Select Dropdown:**
```
Style:
- Same as Text Input
- Icon: Chevron down (right side)

Options:
- Background: White
- Border: 1px solid Gray 200
- Shadow: Large
- Option padding: 12px 16px
- Option hover: Gray 50 background
```

**Radio Buttons:**
```
Style:
- Circle: 20px diameter
- Border: 2px solid Gray 300
- Checked: Primary Blue background, white checkmark
- Label: Body, Gray 800
- Spacing: 8px between radio and label

Group:
- Vertical spacing: 12px
- Group label: Body, Gray 700
```

**Checkboxes:**
```
Style:
- Square: 20px
- Border: 2px solid Gray 300
- Checked: Primary Blue background, white checkmark
- Label: Body, Gray 800
- Spacing: 8px between checkbox and label

Group:
- Vertical spacing: 12px
- Group label: Body, Gray 700
```

### Navigation

**Top Navigation Bar:**
```
Style:
- Background: White
- Border Bottom: 1px solid Gray 100
- Height: 64px
- Padding: 0 24px
- Position: Fixed (sticky)
- Z-index: 100

Content:
- Logo (left): StudyForge text or icon
- Navigation Links (center): Dashboard, New Session, History
- Profile Menu (right): Avatar, dropdown

Links:
- Text: Body, Gray 600
- Hover: Primary Blue
- Active: Primary Blue, font-weight 600
```

**Mobile Navigation:**
```
Style:
- Hamburger menu icon (left)
- Logo (center)
- Profile icon (right)

Menu:
- Slide from left
- Background: White
- Width: 280px
- Shadow: Extra Large
- Links: Vertical list
- Link style: Body, Gray 800, padding 16px
- Link hover: Gray 50 background
```

### Modals

**Standard Modal:**
```
Style:
- Background: White
- Border Radius: 12px
- Shadow: Extra Large
- Max Width: 500px
- Width: 90% (mobile)
- Padding: 24px

Overlay:
- Background: Black with 50% opacity
- Z-index: 200

Content:
- Title: Heading 2, Gray 900
- Body: Body, Gray 600
- Actions: Buttons (right aligned)

Close:
- X icon (top right)
- Click outside to close
- Escape key to close
```

**Confirmation Modal:**
```
Style:
- Same as Standard Modal
- Icon: Warning or Info (top center)
- Title: "Are you sure?"
- Body: Explanation of action
- Actions: Cancel (secondary), Confirm (primary)
```

### Alerts

**Success Alert:**
```
Style:
- Background: Success Green with 10% opacity
- Border: 1px solid Success Green
- Border Left: 4px solid Success Green
- Border Radius: 6px
- Padding: 16px
- Text: Gray 800

Icon:
- Checkmark circle (left side)
- Color: Success Green
```

**Error Alert:**
```
Style:
- Background: Error Red with 10% opacity
- Border: 1px solid Error Red
- Border Left: 4px solid Error Red
- Border Radius: 6px
- Padding: 16px
- Text: Gray 800

Icon:
- X circle (left side)
- Color: Error Red
```

**Warning Alert:**
```
Style:
- Background: Warning Orange with 10% opacity
- Border: 1px solid Warning Orange
- Border Left: 4px solid Warning Orange
- Border Radius: 6px
- Padding: 16px
- Text: Gray 800

Icon:
- Exclamation triangle (left side)
- Color: Warning Orange
```

**Info Alert:**
```
Style:
- Background: Primary Blue with 10% opacity
- Border: 1px solid Primary Blue
- Border Left: 4px solid Primary Blue
- Border Radius: 6px
- Padding: 16px
- Text: Gray 800

Icon:
- Info circle (left side)
- Color: Primary Blue
```

---

## 4. Page-Specific Designs

### Landing Page

**Hero Section:**
```
Layout:
- Full width
- Centered content
- Background: Gradient (White to Gray 50)
- Padding: 96px 24px

Content:
- Headline: Display size, Gray 900
  "Transform Your Study Materials into Learning Tools"
- Subheadline: Body Large, Gray 600
  "Paste your notes, get summaries, flashcards, and quizzes instantly"
- CTA Button: Primary Button, large
  "Get Started Free"
- Social Proof: Caption, Gray 600
  "Join 1,000+ students studying smarter"

Visual:
- Hero image or illustration (right side on desktop)
- Show app screenshot or animated demo
```

**Features Section:**
```
Layout:
- 3 columns (desktop), 1 column (mobile)
- Background: White
- Padding: 80px 24px

Feature Cards:
- Icon: 48px, Primary Blue
- Title: Heading 3, Gray 900
- Description: Body, Gray 600
- Spacing: 24px between cards

Features:
1. "Instant Summaries" — Get concise overviews of any content
2. "Smart Flashcards" — Active recall for better retention
3. "Practice Quizzes" — Test your knowledge instantly
```

**How It Works Section:**
```
Layout:
- 3 steps (horizontal on desktop, vertical on mobile)
- Background: Gray 50
- Padding: 80px 24px

Steps:
1. "Paste Your Notes"
   - Icon: Document
   - Description: "Copy and paste your study material"

2. "AI Generates Materials"
   - Icon: Sparkles
   - Description: "Our AI creates summaries, flashcards, and quizzes"

3. "Study Smarter"
   - Icon: Graduation cap
   - Description: "Review and test your knowledge"
```

**CTA Section:**
```
Layout:
- Full width
- Centered content
- Background: Primary Blue
- Padding: 80px 24px

Content:
- Headline: Display size, White
  "Ready to Study Smarter?"
- Subheadline: Body Large, White with 80% opacity
  "Start transforming your study materials today"
- CTA Button: White background, Primary Blue text
  "Get Started Free"
```

---

### Dashboard

**Header:**
```
Layout:
- Flexbox, space between
- Padding: 24px 0

Content:
- Left: "Welcome back, [Name]" — Heading 1, Gray 900
- Right: "New Study Session" — Primary Button
```

**Quick Stats (Optional):**
```
Layout:
- 4 columns (desktop), 2 columns (tablet), 1 column (mobile)
- Background: White
- Border: 1px solid Gray 100
- Border Radius: 8px
- Padding: 24px
- Margin Bottom: 24px

Stats:
1. "Total Sessions" — Number, Gray 900
2. "Flashcards Created" — Number, Gray 900
3. "Quizzes Taken" — Number, Gray 900
4. "Study Time" — Number, Gray 900
```

**Recent Sessions:**
```
Layout:
- Section title: "Recent Sessions" — Heading 2, Gray 900
- Grid: 3 columns (desktop), 2 columns (tablet), 1 column (mobile)
- Gap: 24px

Session Card:
- Background: White
- Border: 1px solid Gray 100
- Border Radius: 8px
- Padding: 24px
- Hover: Shadow Medium

Content:
- Title: Heading 4, Gray 900
- Date: Caption, Gray 600
- Content Types: Badges (Summary, Flashcards, Quiz)
- Preview: Body Small, Gray 600 (first 100 characters)
- Actions: "View" button (Primary)

Empty State:
- Icon: Document with plus
- Title: "No sessions yet"
- Description: "Create your first study session to get started"
- CTA: "Create Session" — Primary Button
```

---

### Create Study Session Page

**Header:**
```
Layout:
- Padding: 24px 0

Content:
- Title: "New Study Session" — Heading 1, Gray 900
- Subtitle: "Paste your study material below" — Body, Gray 600
```

**Input Section:**
```
Layout:
- Card with textarea
- Margin Bottom: 24px

Card:
- Background: White
- Border: 1px solid Gray 100
- Border Radius: 8px
- Padding: 24px

Textarea:
- Full width
- Min Height: 400px
- Placeholder: "Paste your lecture notes, textbook content, or any study material here..."
- Character counter: Bottom right

Helper Text:
- "Minimum 100 characters, maximum 50,000 characters"
- "Better input = better output"
```

**Generate Button:**
```
Layout:
- Centered
- Margin Top: 24px

Button:
- Primary Button, large
- Text: "Generate Study Materials"
- Icon: Sparkles (left)
- Loading state: Spinner, text hidden
```

**Loading State:**
```
Layout:
- Centered content
- Margin Top: 48px

Content:
- Spinner: Large, Primary Blue
- Title: "Generating your study materials..."
- Steps (with checkmarks when complete):
  1. "Analyzing content..."
  2. "Creating summary..."
  3. "Building flashcards..."
  4. "Generating quiz..."
- Estimated time: "This usually takes 15-30 seconds"
```

---

### Study Session Results Page

**Header:**
```
Layout:
- Flexbox, space between
- Padding: 24px 0

Content:
- Left:
  - Title: Session title — Heading 1, Gray 900
  - Date: Caption, Gray 600
- Right:
  - "Back to Dashboard" — Ghost Button
```

**Results Overview:**
```
Layout:
- Grid: 4 columns (desktop), 2 columns (tablet), 1 column (mobile)
- Gap: 24px
- Margin Bottom: 32px

Cards:
1. Summary Card
   - Icon: Document
   - Title: "Summary"
   - Description: "Concise overview of your content"
   - Action: "Read Summary" — Primary Button

2. Flashcards Card
   - Icon: Cards
   - Title: "Flashcards"
   - Description: "[X] cards ready for review"
   - Action: "Study Flashcards" — Primary Button

3. Quiz Card
   - Icon: Question mark
   - Title: "Quiz"
   - Description: "[X] questions to test your knowledge"
   - Action: "Take Quiz" — Primary Button

4. Key Terms Card (if generated)
   - Icon: Key
   - Title: "Key Terms"
   - Description: "[X] important terms defined"
   - Action: "View Terms" — Primary Button
```

**Input Preview:**
```
Layout:
- Card
- Margin Bottom: 32px

Card:
- Background: Gray 50
- Border: 1px solid Gray 100
- Border Radius: 8px
- Padding: 24px

Content:
- Title: "Original Content" — Heading 4, Gray 900
- Text: Body, Gray 700 (truncated to 500 characters)
- "Show more" link if truncated
```

---

### Flashcard Viewer Page

**Header:**
```
Layout:
- Minimal top bar
- Padding: 16px 24px

Content:
- Left: "Back to Session" — Ghost Button
- Center: "Flashcards" — Heading 3, Gray 900
- Right: Progress — "5 of 20" — Body, Gray 600
```

**Flashcard Area:**
```
Layout:
- Centered card
- Max Width: 600px
- Margin: 48px auto
- Padding: 0 24px

Card:
- Background: White
- Border: 2px solid Gray 100
- Border Radius: 12px
- Padding: 48px
- Min Height: 300px
- Display: Flex, centered content
- Shadow: Large
- Cursor: pointer
- Transition: all 0.3s ease

Content:
- Question/Answer: Heading 3, Gray 900, centered
- Font Size: 20px
- Line Height: 1.6

Flip Animation:
- Rotate Y 180deg
- Duration: 0.3s
- Perspective: 1000px
```

**Controls:**
```
Layout:
- Centered below card
- Margin Top: 32px
- Gap: 16px

Buttons:
- "Previous" — Secondary Button
- "Shuffle" — Ghost Button
- "Next" — Primary Button

Progress Bar:
- Full width
- Height: 8px
- Background: Gray 200
- Fill: Primary Blue
- Border Radius: 4px
```

**Completion State:**
```
Layout:
- Centered content
- Margin Top: 48px

Content:
- Icon: Checkmark circle, Success Green, 64px
- Title: "Great job!" — Heading 2, Gray 900
- Description: "You've reviewed all flashcards" — Body, Gray 600
- Stats:
  - "Cards reviewed: 20"
  - "Time spent: 5 minutes"
- Actions:
  - "Review Again" — Secondary Button
  - "Shuffle & Review" — Primary Button
  - "Take Quiz" — Primary Button
```

---

### Quiz Page

**Header:**
```
Layout:
- Minimal top bar
- Padding: 16px 24px

Content:
- Left: "Back to Session" — Ghost Button
- Center: "Quiz" — Heading 3, Gray 900
- Right: Progress — "Question 5 of 10" — Body, Gray 600
```

**Question Area:**
```
Layout:
- Centered content
- Max Width: 700px
- Margin: 48px auto
- Padding: 0 24px

Question Card:
- Background: White
- Border: 1px solid Gray 100
- Border Radius: 8px
- Padding: 32px
- Margin Bottom: 24px

Content:
- Question Number: Caption, Gray 600
- Question Text: Heading 3, Gray 900
```

**Options:**
```
Layout:
- Vertical list
- Gap: 12px

Option:
- Background: White
- Border: 2px solid Gray 200
- Border Radius: 8px
- Padding: 16px 20px
- Cursor: pointer
- Transition: all 0.2s ease

States:
- Default: Gray 200 border
- Hover: Primary Blue border, Gray 50 background
- Selected: Primary Blue border, Primary Blue with 10% background
- Correct: Success Green border, Success Green with 10% background
- Incorrect: Error Red border, Error Red with 10% background

Content:
- Radio button (left)
- Option text (right): Body, Gray 800
```

**Controls:**
```
Layout:
- Flexbox, space between
- Margin Top: 32px

Left:
- "Previous Question" — Secondary Button

Right:
- "Next Question" — Primary Button
- "Submit Quiz" — Primary Button (on last question)
```

**Results Page:**
```
Layout:
- Centered content
- Max Width: 600px
- Margin: 48px auto
- Padding: 0 24px

Score Card:
- Background: White
- Border: 1px solid Gray 100
- Border Radius: 12px
- Padding: 48px
- Text: Center

Content:
- Icon: Trophy, Primary Blue, 64px
- Score: Display size, Gray 900
  "8/10"
- Percentage: Heading 2, Gray 600
  "80%"
- Time: Body, Gray 600
  "Completed in 5 minutes"

Actions:
- "Review Answers" — Secondary Button
- "Retake Quiz" — Primary Button
- "Back to Session" — Ghost Button
```

**Answer Review:**
```
Layout:
- List of questions
- Gap: 24px

Question Card:
- Background: White
- Border: 1px solid Gray 100
- Border Radius: 8px
- Padding: 24px

Content:
- Question Number: Caption, Gray 600
- Question Text: Heading 4, Gray 900
- Options: Same as quiz, with correct/incorrect highlighting
- Explanation: Body, Gray 600, Gray 50 background, padding 16px
```

---

### History Page

**Header:**
```
Layout:
- Padding: 24px 0

Content:
- Title: "Study History" — Heading 1, Gray 900
- Subtitle: "View and manage your past study sessions" — Body, Gray 600
```

**Search and Filters:**
```
Layout:
- Flexbox, space between
- Margin Bottom: 24px

Left:
- Search input with icon
- Placeholder: "Search sessions..."

Right:
- Sort dropdown: "Newest first", "Oldest first", "A-Z"
- Filter dropdown: "All types", "Summary", "Flashcards", "Quiz"
```

**Session List:**
```
Layout:
- Grid: 3 columns (desktop), 2 columns (tablet), 1 column (mobile)
- Gap: 24px

Session Card:
- Same as Dashboard session card
- Additional: Delete button (icon, top right)

Empty State:
- Icon: Folder open
- Title: "No sessions found"
- Description: "Try adjusting your search or filters"
```

---

### Profile Page

**Header:**
```
Layout:
- Padding: 24px 0

Content:
- Title: "Profile Settings" — Heading 1, Gray 900
```

**Profile Card:**
```
Layout:
- Card
- Margin Bottom: 24px

Card:
- Background: White
- Border: 1px solid Gray 100
- Border Radius: 8px
- Padding: 32px

Content:
- Avatar: 80px circle, Primary Blue background, initials
- Name: Heading 3, Gray 900
- Email: Body, Gray 600
- Member Since: Caption, Gray 600
- Total Sessions: Body, Gray 600

Actions:
- "Edit Profile" — Secondary Button
- "Change Password" — Secondary Button
```

**Account Stats:**
```
Layout:
- Grid: 4 columns (desktop), 2 columns (tablet), 1 column (mobile)
- Gap: 24px

Stat Card:
- Background: Gray 50
- Border: 1px solid Gray 100
- Border Radius: 8px
- Padding: 24px
- Text: Center

Content:
- Number: Heading 2, Primary Blue
- Label: Caption, Gray 600

Stats:
1. Total Sessions
2. Flashcards Created
3. Quizzes Taken
4. Study Time
```

---

## 5. Mobile Responsiveness

### Mobile-First Approach

**Design Strategy:**
- Start with mobile layout
- Add complexity for larger screens
- Touch-friendly targets (minimum 44px)
- Readable text (minimum 16px)

### Mobile-Specific Patterns

**Navigation:**
```
Desktop: Horizontal top bar
Mobile: Hamburger menu with slide-out drawer

Hamburger Menu:
- Icon: 3 horizontal lines
- Size: 24px
- Padding: 12px
- Position: Top left

Drawer:
- Width: 280px
- Background: White
- Shadow: Extra Large
- Links: Vertical list
- Link padding: 16px
- Link font: Body, Gray 800
```

**Cards:**
```
Desktop: 3 columns
Tablet: 2 columns
Mobile: 1 column (full width)
```

**Forms:**
```
Desktop: Labels above inputs
Mobile: Labels above inputs (same)

Textarea:
- Desktop: 400px height
- Mobile: 300px height
```

**Buttons:**
```
Desktop: Auto width
Mobile: Full width (for primary actions)
```

**Modals:**
```
Desktop: Max width 500px, centered
Mobile: Full width, bottom sheet style
```

### Touch Interactions

**Tap Targets:**
- Minimum 44px × 44px
- Adequate spacing between targets
- Clear visual feedback on tap

**Gestures:**
- Swipe left/right for flashcards
- Tap to flip flashcards
- Pull to refresh (optional)

---

## 6. Visual Hierarchy

### Information Hierarchy

**Level 1: Primary Actions**
- Large, prominent buttons
- Primary Blue color
- Clear, action-oriented text
- Examples: "Generate Study Materials", "Start Quiz"

**Level 2: Secondary Actions**
- Smaller buttons
- Secondary or Ghost style
- Supporting actions
- Examples: "View Details", "Edit", "Delete"

**Level 3: Tertiary Actions**
- Text links
- Minimal visual weight
- Navigation and exploration
- Examples: "Learn more", "Show more", "Back to..."

### Content Hierarchy

**Headings:**
- Display: Hero sections
- H1: Page titles
- H2: Section titles
- H3: Card titles
- H4: Subsections

**Body Text:**
- Large: Important paragraphs
- Regular: Standard content
- Small: Secondary information
- Caption: Metadata, labels

### Visual Weight

**High Weight:**
- Primary buttons
- Error messages
- Important numbers
- Call-to-action elements

**Medium Weight:**
- Secondary buttons
- Card titles
- Form labels
- Success messages

**Low Weight:**
- Body text
- Captions
- Borders
- Backgrounds

---

## 7. Trust, Clarity, and Motivation Design Cues

### Trust Signals

**Professional Design:**
- Clean, modern aesthetic
- Consistent styling
- High-quality icons
- Smooth animations

**Security Indicators:**
- HTTPS badge (if applicable)
- Privacy policy link
- Terms of service link
- Secure login indicators

**Social Proof:**
- User testimonials
- Usage statistics
- Trust badges
- Partner logos (if applicable)

### Clarity Design

**Clear Labels:**
- Descriptive button text
- Helpful form labels
- Informative tooltips
- Clear error messages

**Visual Feedback:**
- Loading states
- Success confirmations
- Error alerts
- Progress indicators

**Consistent Patterns:**
- Same button styles throughout
- Same card patterns
- Same form layouts
- Same navigation structure

### Motivation Design

**Progress Indicators:**
- Progress bars
- Step counters
- Completion percentages
- Achievement badges

**Celebration Moments:**
- Success animations
- Confetti on completion
- Congratulatory messages
- Achievement unlocks

**Encouragement:**
- Positive microcopy
- Helpful tips
- Study streaks (future)
- Leaderboards (future)

### Microcopy Guidelines

**Tone:**
- Friendly but professional
- Encouraging but not patronizing
- Clear but not robotic
- Helpful but not overwhelming

**Examples:**
- "Great job!" (after completing flashcards)
- "You're making progress!" (after creating session)
- "Ready to test your knowledge?" (before quiz)
- "Almost there!" (during loading)

---

## 8. Accessibility

### WCAG 2.1 AA Compliance

**Color Contrast:**
- Normal text: 4.5:1 ratio minimum
- Large text: 3:1 ratio minimum
- UI components: 3:1 ratio minimum

**Keyboard Navigation:**
- All interactive elements focusable
- Visible focus indicators
- Logical tab order
- Skip links for main content

**Screen Readers:**
- Semantic HTML
- ARIA labels for icons
- Alt text for images
- Form labels associated with inputs

### Focus Indicators

**Style:**
- Outline: 2px solid Primary Blue
- Outline offset: 2px
- Border radius: 4px

**When Visible:**
- On focus (keyboard navigation)
- Not on click (mouse interaction)

### Reduced Motion

**Respect User Preferences:**
- Check `prefers-reduced-motion`
- Disable animations if preferred
- Provide alternative feedback

---

## 9. Animation and Motion

### Animation Principles

**Purpose:**
- Provide feedback
- Guide attention
- Create delight
- Improve perceived performance

**Timing:**
- Fast: 100-200ms (micro-interactions)
- Normal: 200-300ms (transitions)
- Slow: 300-500ms (complex animations)

**Easing:**
- Ease-in-out: Most animations
- Ease-out: Entering elements
- Ease-in: Exiting elements

### Common Animations

**Button Hover:**
- Background color change
- Duration: 200ms
- Easing: ease-in-out

**Card Hover:**
- Shadow change
- Transform: translateY(-2px)
- Duration: 200ms
- Easing: ease-out

**Flashcard Flip:**
- Rotate Y: 180deg
- Duration: 300ms
- Easing: ease-in-out

**Modal Enter:**
- Opacity: 0 → 1
- Transform: scale(0.95) → scale(1)
- Duration: 200ms
- Easing: ease-out

**Modal Exit:**
- Opacity: 1 → 0
- Transform: scale(1) → scale(0.95)
- Duration: 150ms
- Easing: ease-in

**Loading Spinner:**
- Rotate: 360deg
- Duration: 1s
- Easing: linear
- Iteration: infinite

**Progress Bar:**
- Width: 0% → 100%
- Duration: 1s
- Easing: ease-out

---

## 10. Design Assets

### Icons

**Icon Library:**
- Heroicons (by Tailwind Labs)
- Style: Outline
- Size: 24px (default), 20px (small), 32px (large)

**Common Icons:**
- Document: Study materials
- Sparkles: AI generation
- Cards: Flashcards
- Question mark: Quiz
- Checkmark: Success
- X: Close/Error
- Chevron right: Navigation
- Search: Search
- Trash: Delete
- Pencil: Edit
- User: Profile
- Cog: Settings

### Illustrations

**Style:**
- Minimal, line-based
- Primary Blue and Purple colors
- Friendly, approachable characters
- Study-related themes

**Usage:**
- Empty states
- Onboarding
- Success messages
- Landing page

### Images

**Hero Image:**
- App screenshot or mockup
- Show key features
- Professional, polished look

**Background Images:**
- Subtle patterns
- Gradients
- Abstract shapes

---

## Summary

### Design System Highlights

**Colors:**
- Primary Blue for trust and focus
- Purple for creativity and motivation
- Green for success
- Red for errors
- Neutral grays for text and backgrounds

**Typography:**
- Inter font family
- Clear hierarchy with sizes and weights
- Readable line heights
- Consistent spacing

**Components:**
- Buttons (Primary, Secondary, Ghost)
- Cards (Standard, Interactive, Study)
- Forms (Input, Textarea, Select, Radio, Checkbox)
- Navigation (Top bar, Mobile menu)
- Modals (Standard, Confirmation)
- Alerts (Success, Error, Warning, Info)

### Key Design Principles

1. **Clean and Uncluttered** — White space, clear hierarchy
2. **Student-Focused** — Motivating, encouraging, supportive
3. **Professional** — Trustworthy, reliable, polished
4. **Accessible** — Usable by everyone
5. **Consistent** — Same patterns throughout

### Next Steps

With UI/UX design defined, we move to:

**Phase 7:** Break down pages and modules  
**Phase 8:** Design AI output strategy  
**Phase 9:** Create development roadmap  

Each phase will reference these design specifications for implementation.

---

*Document Version: 1.0*  
*Last Updated: 2026-03-25*  
*Status: Phase 6 Complete*
