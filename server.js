const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = process.env.PORT || 3000;
const SECRET_KEY = process.env.SECRET_KEY || 'BROZEK_EMERGENCY_2026';

const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;

    // 1. UPDATE STATUS API
    if (pathname === '/update-status' && req.method === 'GET') {
        const { status, key } = parsedUrl.query;

        if (key !== SECRET_KEY) {
            res.writeHead(403, { 'Content-Type': 'text/plain' });
            return res.end('UNAUTHORIZED: SECRET KEY INVALID');
        }

        if (status !== 'GREEN' && status !== 'RED') {
            res.writeHead(400, { 'Content-Type': 'text/plain' });
            return res.end('INVALID STATUS: USE GREEN OR RED');
        }

        const newStatus = {
            status,
            lastUpdated: new Date().toISOString()
        };

        fs.writeFileSync(path.join(__dirname, 'status.json'), JSON.stringify(newStatus, null, 2));
        
        res.writeHead(200, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ message: `STATUS UPDATED TO ${status}`, data: newStatus }));
    }

    // 2. STATIC FILE SERVING
    let filePath = path.join(__dirname, 'public', pathname === '/' ? 'index.html' : pathname);
    
    // Special case for status.json (needs to be served from root)
    if (pathname === '/status.json') {
        filePath = path.join(__dirname, 'status.json');
    }

    const extname = path.extname(filePath);
    const contentTypes = {
        '.html': 'text/html',
        '.css': 'text/css',
        '.js': 'text/javascript',
        '.json': 'application/json',
        '.png': 'image/png',
        '.jpg': 'image/jpg'
    };

    const contentType = contentTypes[extname] || 'text/plain';

    fs.readFile(filePath, (error, content) => {
        if (error) {
            if (error.code === 'ENOENT') {
                res.writeHead(404);
                res.end('404 NOT FOUND');
            } else {
                res.writeHead(500);
                res.end('500 INTERNAL ERROR');
            }
        } else {
            res.writeHead(200, { 'Content-Type': contentType });
            res.end(content, 'utf-8');
        }
    });
});

server.listen(PORT, () => {
    console.log(`
    -------------------------------------------
    BROZEK TERMINAL RUNNING ON PORT ${PORT}
    -------------------------------------------
    SECRET KEY: ${SECRET_KEY}
    UPDATE STATUS VIA:
    http://localhost:${PORT}/update-status?status=RED&key=${SECRET_KEY}
    -------------------------------------------
    `);
});