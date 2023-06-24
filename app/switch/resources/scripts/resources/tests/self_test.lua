local Cache    = require 'resources.functions.cache'
local Database = require 'resources.functions.database'

Database.__self_test__({
  "native",
  "luasql",
  "odbc",
  "odbcpool",
},
"system")

Cache._self_test()
