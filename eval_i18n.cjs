const fs = require('fs');
let code = fs.readFileSync('resources/js_backup/i18n.js', 'utf-8');
let messagesMatch = code.match(/const messages = (\{[\s\S]*?\n\});/);
if (messagesMatch) {
   let messagesObj = eval('(' + messagesMatch[1] + ')');
   fs.writeFileSync('i18n_messages.json', JSON.stringify(messagesObj, null, 2));
}
