const fs = require('fs');
const path = require('path');

// Script pour précharger les ressources critiques
function generatePreloadLinks() {
  const distPath = path.join(__dirname, '../dist/blob-front');
  const indexPath = path.join(distPath, 'index.html');
  
  if (!fs.existsSync(indexPath)) {
    console.log('Index.html not found. Run build first.');
    return;
  }
  
  let indexContent = fs.readFileSync(indexPath, 'utf8');
  
  // Trouver les fichiers CSS et JS critiques
  const cssFiles = fs.readdirSync(distPath)
    .filter(file => file.endsWith('.css'))
    .sort((a, b) => {
      // Prioriser le fichier principal
      if (a.includes('styles')) return -1;
      if (b.includes('styles')) return 1;
      return 0;
    });
  
  const jsFiles = fs.readdirSync(distPath)
    .filter(file => file.endsWith('.js'))
    .sort((a, b) => {
      // Prioriser le fichier principal
      if (a.includes('main')) return -1;
      if (b.includes('main')) return 1;
      return 0;
    });
  
  // Générer les liens de préchargement
  let preloadLinks = '';
  
  // Précharger le CSS critique avec priorité haute
  cssFiles.slice(0, 2).forEach(file => {
    preloadLinks += `    <link rel="preload" href="./${file}" as="style" onload="this.onload=null;this.rel='stylesheet'">\n`;
  });
  
  // Précharger le JS principal avec priorité haute
  jsFiles.slice(0, 1).forEach(file => {
    preloadLinks += `    <link rel="preload" href="./${file}" as="script" fetchpriority="high">\n`;
  });
  
  // Ajouter des hints de performance
  preloadLinks += `    <link rel="dns-prefetch" href="//fonts.googleapis.com">\n`;
  preloadLinks += `    <link rel="dns-prefetch" href="//www.google.com">\n`;
  preloadLinks += `    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>\n`;
  
  // Insérer les liens dans le head
  const headEndIndex = indexContent.indexOf('</head>');
  if (headEndIndex !== -1) {
    indexContent = indexContent.slice(0, headEndIndex) + 
                   preloadLinks + 
                   indexContent.slice(headEndIndex);
  }
  
  fs.writeFileSync(indexPath, indexContent);
  console.log('Preload links added to index.html');
}

generatePreloadLinks();
