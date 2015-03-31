base64 = {}

-- encode a string and return a base64 string
function base64.encode(s)
	local mime = require("mime");
	return (mime.b64(s));
end

--decode a base64 string and return a string
function base64.decode(s)
	local mime = require("mime");
	return (mime.unb64(s));
end