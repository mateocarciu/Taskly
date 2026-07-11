# Taskly

Taskly is a modern, collaborative task management application for teams. It allows teams to create, organize, and track tasks dynamically on an interactive Kanban board.

This project was bootstrapped from the official [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit).

## Features

- **Interactive Kanban Board:** Drag-and-drop tasks between columns, rename columns, and adjust task priority sequences.
- **Advanced Task Filters:** Filter bar for finding tasks by:
  - Search keywords (debounced title & description search).
  - Assignee (filtered by specific team members or unassigned tasks).
  - Tags (multi-select filter).
  - Due Dates (shortcuts like Today, This Week, Overdue, or custom exact dates and date ranges).
- **Task Details & Collaboration:** 
  - Task editing with rich description styling (Tiptap editor), comments with threaded replies, and a detailed activity log timeline.
  - Discussion threads with nested replies.
  - Detailed activity log timeline for full auditability.
  - **Document Uploads:** Attach files directly to tasks (currently supporting Images and PDFs).
  - **Smart Link Previews:** Automatic, rich previews for URLs embedded in task descriptions and comments.
- **Workload Dashboard:** Team workspace overview featuring task metrics, urgent "To Handle Now" tasks, column breakdowns, and a recent activity feed.

## Tech Stack

- **Backend:** Laravel 12 (PHP >= 8.4), Inertia.js V3
- **Frontend:** Vue 3
- **Styling:** Tailwind CSS & Shadcn
- **CI/CD:** GitHub Actions workflow for automated testing, linting and deployment.
- **Deployment:** Docker, with a production-ready Dockerfile included in the repository.

## Installation

### Prerequisites

Make sure you have the following installed on your local system:
- **PHP** >= 8.4
- **Composer**
- **Node.js** >= 20
- **NPM**

### Getting Started

1. **Clone the repository** to your local environment.
2. **Install dependencies and seed the database:**
   ```bash
   composer run setup
   ```
   *This command installs Composer and JavaScript dependencies, initializes your `.env` configuration, generates the app key, and runs migrations with database seeds.*

3. **Start the development server:**
   ```bash
   composer run dev
   ```
   *This starts the Artisan server and the Vite dev server concurrently.*

4. Open [http://localhost:8000](http://localhost:8000) in your browser.

## Seeded Test Accounts

The seeded database contains **3 teams** and **6 users**. Each user is pre-assigned to a team:

| Team | User | Email | Password |
| :--- | :--- | :--- | :--- |
| **Team 1** | User 1, 2, 3 | `test1@example.com` to `test3@example.com` | `password` |
| **Team 2** | User 4, 5 | `test4@example.com` & `test5@example.com` | `password` |
| **Team 3** | User 6 | `test6@example.com` | `password` |

*Note: You can also register a new account and create a custom team from the login page.*

