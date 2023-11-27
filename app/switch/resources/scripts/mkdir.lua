require "resources.functions.mkdir"

local path = argv[1]

if path and #path > 0 then
	mkdir(path)
end
