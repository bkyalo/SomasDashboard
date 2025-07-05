# Moodle Analytics Dashboard

A responsive web dashboard for visualizing Moodle LMS data and analytics. This dashboard provides insights into user activity, course statistics, and other key metrics from your Moodle installation.

## Features

- Real-time statistics on users, courses, and categories
- Active user tracking
- Clean, modern UI with responsive design
- Interactive charts and visualizations
- Recent activity feed
- Mobile-friendly interface

## Requirements

- PHP 7.4 or higher
- Moodle 3.9 or higher with web services enabled
- Web server (Apache/Nginx)
- MySQL/MariaDB (for future features)

## Installation

1. Clone this repository to your web server:
   ```
   git clone https://github.com/yourusername/moodle-analytics-dashboard.git
   ```

2. Copy `config.sample.php` to `config.php` and update with your Moodle API credentials:
   ```php
   define('MOODLE_API_URL', 'https://your-moodle-site.com/webservice/rest/server.php');
   define('MOODLE_API_TOKEN', 'your-webservice-token');
   ```

3. Make sure the web server has write permissions to the following directories:
   - `/cache`
   - `/logs`

4. Access the dashboard through your web browser:
   ```
   http://your-server/moodle-analytics-dashboard/
   ```

## Configuration

### Moodle Web Services Setup

1. In your Moodle site, go to Site administration > Plugins > Web services > External services
2. Create a new service
3. Add the following functions:
   - core_webservice_get_site_info
   - core_user_get_users
   - core_course_get_courses
   - core_course_get_categories
4. Create an external service user with appropriate permissions
5. Generate a token for the user

### Dashboard Configuration

Edit the `config.php` file to customize:
- API endpoints
- Database connection
- Caching settings
- UI preferences

## Usage

1. **Dashboard Overview**
   - View key statistics at a glance
   - Monitor active users in real-time
   - Track course and user growth

2. **User Analytics**
   - View user activity patterns
   - Track course enrollments
   - Monitor user engagement

3. **Course Analytics**
   - Track course completion rates
   - Monitor assignment submissions
   - Analyze quiz performance

## Development

### Dependencies

- [Tailwind CSS](https://tailwindcss.com/)
- [Alpine.js](https://alpinejs.dev/)
- [Chart.js](https://www.chartjs.org/)
- [Font Awesome](https://fontawesome.com/)

### Building Assets

1. Install Node.js and npm
2. Install dependencies:
   ```
   npm install
   ```
3. Build assets:
   ```
   npm run build
   ```

### Coding Standards

- Follow PSR-12 coding standards
- Use PHP 7.4+ features where appropriate
- Document all functions and classes with PHPDoc
- Write unit tests for new features

## Security

- Keep your Moodle API token secure
- Restrict access to the dashboard using .htaccess or server configuration
- Regularly update dependencies
- Follow security best practices for PHP applications

## License

This project is open-source and available under the MIT License.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For support, please open an issue in the GitHub repository.
