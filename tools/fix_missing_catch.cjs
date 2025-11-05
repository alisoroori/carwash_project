/* 
  Scan .php/.js files in backend/dashboard and frontend for "try { ... }" blocks
  that are not followed by catch/finally, insert a conservative catch(e){console.error(e);} 
  Backup original files to .bak before changing.
  Usage: node tools/fix_missing_catch.js
*/
const fs = require('fs');
const path = require('path');
const glob = require('glob');

const ROOT = process.cwd();
const PATTERNS = [
  path.join(ROOT, 'backend', 'dashboard', '**', '*.{php,js}'),
  path.join(ROOT, 'frontend', 'js', '**', '*.js'),
];

function backup(file) {
  const bak = file + '.bak';
  if (!fs.existsSync(bak)) fs.copyFileSync(file, bak);
}

function fixContent(content) {
  // Regex: find "try { ... }" where after the closing brace there is NOT catch or finally.
  // This uses a balanced-brace heuristic (simple) — will handle most inline cases.
  // Warning: not perfect for deeply nested blocks with unmatched braces inside strings.
  let changed = false;
  const tryRegex = /try\s*\{\s*([\s\S]*?)\s*\}(?!\s*(catch|finally))/g;
  const replacement = (all, inner) => {
    changed = true;
    // keep indentation: determine indent from 'try' line
    const indentMatch = all.match(/^\s*/);
    const indent = indentMatch ? indentMatch[0] : '';
    const catchBlock = `\n${indent}catch (e) {\n${indent}  console.error('Auto-insert catch:', e);\n${indent}}\n`;
    return all + catchBlock;
  };
  const newContent = content.replace(tryRegex, replacement);
  return { newContent, changed };
}

(async function main(){
  console.log('Scanning for try blocks missing catch/finally...');
  let totalFixed = 0;
  const files = PATTERNS.flatMap(p => glob.sync(p, { nodir: true }));
  for (const file of files) {
    try {
      const content = fs.readFileSync(file, 'utf8');
      const { newContent, changed } = fixContent(content);
      if (changed) {
        backup(file);
        fs.writeFileSync(file, newContent, 'utf8');
        console.log('Patched:', file);
        totalFixed++;
      }
    } catch (err) {
      console.error('Error processing', file, err && err.message ? err.message : err);
    }
  }
  console.log(`Done. Files patched: ${totalFixed}. Review .bak files for safety.`);
})();