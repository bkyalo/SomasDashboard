# Moodle Analytics Dashboard

A web-based dashboard for analyzing Moodle course data and user interactions.

## Features

- View Moodle course categories
- Browse courses by category
- View detailed course contents
- Responsive design for all devices

## Prerequisites

- Node.js (v14 or later)
- npm (comes with Node.js)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/moodle-analytics.git
   cd moodle-analytics
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Configure environment variables:
   Create a `.env` file in the root directory and add:
   ```
   MOODLE_URL=your_moodle_url
   MOODLE_TOKEN=your_moodle_token
   PORT=3001
   ```

## Running the Application

1. Start the development server:
   ```bash
   npm run dev
   ```

2. Open your browser and navigate to:
   ```
   http://localhost:3001
   ```

## Project Structure

- `server.js` - Main server file with API endpoints
- `public/` - Static files (HTML, CSS, JS)
- `js/app.js` - Frontend JavaScript
- `css/styles.css` - Custom styles

## API Endpoints

- `GET /api/categories` - Get all course categories
- `GET /api/courses/:categoryid` - Get courses in a category
- `GET /api/course/:courseid/contents` - Get contents of a specific course

## License

This project is licensed under the MIT License.
