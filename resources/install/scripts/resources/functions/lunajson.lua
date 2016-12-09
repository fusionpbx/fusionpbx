local newdecoder = require 'resources.functions.lunajson.decoder'
local newencoder = require 'resources.functions.lunajson.encoder'
local sax = require 'resources.functions.lunajson.sax'
-- If you need multiple contexts of decoder and/or encoder,
-- you can require lunajson.decoder and/or lunajson.encoder directly.
return {
	decode = newdecoder(),
	encode = newencoder(),
	newparser = sax.newparser,
	newfileparser = sax.newfileparser,
}
