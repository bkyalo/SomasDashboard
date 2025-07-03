const express = require('express');
const cors = require('cors');
const fetch = require('node-fetch');
const util = require('util');

const app = express();
const PORT = process.env.PORT || 3001;

// Environment variables
const MOODLE_URL = 'https://somas.ouk.ac.ke/webservice/rest/server.php';
const TOKEN = 'd535f9bb93cea06a9163f1159d6032aa';

app.use(cors());
app.use(express.json());
app.use(express.static('.')); // Serve static files from the current directory

// Cache for storing category names to reduce API calls
const categoryCache = new Map();

// Helper function to make Moodle API calls
async function callMoodleWebService(wsfunction, params = {}) {
    try {
        const queryParams = new URLSearchParams({
            wstoken: TOKEN,
            wsfunction: wsfunction,
            moodlewsrestformat: 'json',
            ...params
        });

        const response = await fetch(`${MOODLE_URL}?${queryParams}`);
        const data = await response.json();
        
        if (data.exception) {
            console.error('Moodle API Error:', data);
            throw new Error(data.message || 'Moodle API Error');
        }
        
        return data;
    } catch (error) {
        console.error('Error calling Moodle API:', error);
        throw error;
    }
}

// Get all categories
app.get('/api/categories', async (req, res) => {
    try {
        const categories = await callMoodleWebService('core_course_get_categories');
        res.json(categories);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Get courses in a category
app.get('/api/courses/:categoryid', async (req, res) => {
    try {
        const { categoryid } = req.params;
        const courses = await callMoodleWebService('core_course_get_courses_by_field', {
            'field': 'category',
            'value': categoryid
        });
        res.json(courses);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Get course contents
app.get('/api/course/:courseid/contents', async (req, res) => {
    try {
        const { courseid } = req.params;
        const contents = await callMoodleWebService('core_course_get_contents', {
            'courseid': courseid
        });
        res.json(contents);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Start the server
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});
