const fs = require('fs');
let code = fs.readFileSync('resources/js_backup/i18n.js', 'utf-8');
code = code.replace(/import .*/g, '');
code = code.replace(/export .*/g, '');
// Add a console.log at the end
code += '\nconsole.log(JSON.stringify(messages, null, 2));\n';
fs.writeFileSync('temp_i18n.js', code);
