base64 = {}

-- encode a string and return a base64 string
function base64.encode(s)
	if package.loaded["mime"] then
		local mime = require("mime");
		return (mime.b64(s));
	else
		dofile(scripts_dir.."/resources/functions/base64_alex.lua");
		return base64.enc(s);
	end
end

--decode a base64 string and return a string
function base64.decode(s)
	if package.loaded["mime"] then
		local mime = require("mime");
		return (mime.unb64(s));
	else
		dofile(scripts_dir.."/resources/functions/base64_alex.lua");
		return base64.dec(s);
	end
end