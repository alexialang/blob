const puppeteer = require('puppeteer');
const fs = require('fs').promises;
const path = require('path');
const { spawn } = require('child_process');

const routes = [
  { path: '/', file: 'index.html' },
  { path: '/connexion', file: 'connexion/index.html' },
  { path: '/inscription', file: 'inscription/index.html' },
  { path: '/mentions-legales', file: 'mentions-legales/index.html' },
  { path: '/mot-de-passe-oublie', file: 'mot-de-passe-oublie/index.html' },
  { path: '/a-propos', file: 'a-propos/index.html' },
  { path: '/faire-un-don', file: 'faire-un-don/index.html' }
];

function isPortFree(port) {
  return new Promise((resolve) => {
    const server = require('net').createServer();
    server.listen(port, (err) => {
      if (err) {
        resolve(false);
      } else {
        server.once('close', () => resolve(true));
        server.close();
      }
    });
    server.on('error', () => resolve(false));
  });
}

async function findFreePort(startPort = 8080) {
  let port = startPort;
  while (!(await isPortFree(port))) {
    port++;
    if (port > 9000) {
      throw new Error('Aucun port libre trouvé');
    }
  }
  return port;
}

async function prerender() {
  console.log('Start prerender...');

  const distPath = path.join(__dirname, '../dist/blob-front');

  try {
    await fs.access(distPath);
  } catch (error) {
    console.error('Directory dist/blob-front doesn\'t exist. First launch "ng build"');
    process.exit(1);
  }

  const port = await findFreePort(8080);
  console.log(`Start server on port ${port}...`);

  const server = spawn('npx', ['http-server', distPath, '-p', port.toString(), '--cors'], {
    stdio: 'pipe'
  });

  server.on('error', (err) => {
    console.error('Error: ', err);
  });

  console.log('The server is starting...');
  await new Promise(resolve => setTimeout(resolve, 2000));

  let browser;
  try {
    console.log('Launch Puppeteer...');

    browser = await puppeteer.launch({
      headless: true,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--no-first-run',
        '--no-zygote',
        '--disable-gpu',
        '--disable-web-security',
        '--disable-features=VizDisplayCompositor'
      ]
    });

    for (const route of routes) {
      console.log(`Pre-render of route ${route.path}...`);

      const page = await browser.newPage();
      await page.setViewport({ width: 1280, height: 720 });
      page.on('console', msg => {
        if (msg.type() === 'error') {
          console.log(`   Console error: ${msg.text()}`);
        }
      });

      page.on('pageerror', error => {
        console.log(`   Page error: ${error.message}`);
      });

      try {
        const baseUrl = `http://localhost:${port}`;

        if (route.path === '/') {
          console.log(`   Go to ${baseUrl}`);
          await page.goto(baseUrl, {
            waitUntil: 'networkidle2',
            timeout: 30000
          });
        } else {
          console.log(`   Go to ${baseUrl} then ${route.path}`);
          await page.goto(baseUrl, {
            waitUntil: 'networkidle0',
            timeout: 30000
          });

          await page.waitForSelector('app-root', { timeout: 15000 });
          console.log('   Angular loaded on base page');

          await page.evaluate((path) => {
            window.history.pushState({}, '', path);
            window.dispatchEvent(new PopStateEvent('popstate'));
          }, route.path);

          await new Promise(resolve => setTimeout(resolve, 2000));
        }

        await page.waitForSelector('app-root', { timeout: 15000 });
        if (route.path === '/connexion') {
          await page.waitForSelector('app-login', { timeout: 10000 }).catch(() => {
            console.log('   app-login not found, continue...');
          });
        } else if (route.path === '/inscription') {
          await page.waitForSelector('app-register', { timeout: 10000 }).catch(() => {
            console.log('   app-register not found, continue...');
          });
        }

        await new Promise(resolve => setTimeout(resolve, 1000));

        const currentUrl = await page.url();
        const currentPath = new URL(currentUrl).pathname;
        console.log(`   Current URL: ${currentPath}`);

        const html = await page.content();

        if (html.length < 1000) {
          console.log(`   Content length is short for route ${route.path}`);
        }

        const filePath = path.join(distPath, route.file);
        const dir = path.dirname(filePath);

        try {
          await fs.access(dir);
        } catch {
          await fs.mkdir(dir, { recursive: true });
        }

        await fs.writeFile(filePath, html, 'utf8');

        console.log(`${route.path} → ${route.file} (${Math.round(html.length/1024)}kb)`);
      } catch (error) {
        console.error(`Error for ${route.path}:`, error.message);

        try {
          const fallbackHtml = await page.content();
          const filePath = path.join(distPath, route.file);
          const dir = path.dirname(filePath);

          try {
            await fs.access(dir);
          } catch {
            await fs.mkdir(dir, { recursive: true });
          }

          await fs.writeFile(filePath, fallbackHtml, 'utf8');
          console.log(`   Saved!`);
        } catch (fallbackError) {
          console.error(`   Unable to save: ${fallbackError.message}`);
        }
      }

      await page.close();
    }

  } catch (error) {
    console.error('Err on pre-render:', error);
  } finally {
    if (browser) {
      await browser.close();
    }

    console.log('Stop server...');
    server.kill('SIGTERM');
    
    // Attendre que le serveur se ferme complètement
    await new Promise(resolve => {
      server.on('close', resolve);
      setTimeout(resolve, 3000); // Timeout de sécurité
    });

    console.log('Pre-render finished !');
    process.exit(0);
  }
}

process.on('SIGINT', () => {
  console.log('\nStop process...');
  process.exit(0);
});

process.on('SIGTERM', () => {
  console.log('\nStop process...');
  process.exit(0);
});

prerender().catch((error) => {
  console.error('Fatal error:', error);
  process.exit(1);
});
