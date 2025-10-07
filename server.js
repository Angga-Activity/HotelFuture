const express = require('express')
const app = express()
app.get('/', (req, res) => res.send('API jalan'))
app.listen(3000, () => console.log('Server jalan di http://localhost/hh/index.php'))
