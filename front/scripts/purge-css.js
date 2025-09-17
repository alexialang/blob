const fs = require('fs');
const path = require('path');

// Script pour purger le CSS inutilisé
function purgeCSS() {
  const distPath = path.join(__dirname, '../dist/blob-front');
  const cssFiles = fs.readdirSync(distPath).filter(file => file.endsWith('.css'));
  
  console.log('CSS files found:', cssFiles);
  
  // Pour l'instant, on va juste optimiser les fichiers CSS existants
  cssFiles.forEach(file => {
    const filePath = path.join(distPath, file);
    let content = fs.readFileSync(filePath, 'utf8');
    
    // Supprimer les espaces inutiles
    content = content.replace(/\s+/g, ' ');
    content = content.replace(/;\s*}/g, '}');
    content = content.replace(/{\s*/g, '{');
    content = content.replace(/;\s*/g, ';');
    
    // Supprimer les commentaires
    content = content.replace(/\/\*[\s\S]*?\*\//g, '');
    
    fs.writeFileSync(filePath, content);
    console.log(`Purged CSS: ${file}`);
  });
}

purgeCSS();
