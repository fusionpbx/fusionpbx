local xml = {}

function xml:new(o)
    o = o or {}
    setmetatable(o, self);
    self.__index = self;
    self.xml = {};
    return o;
end

function xml:append(data)
    table.insert(self.xml, data);
end

function xml:build()
    return table.concat(self.xml, "\n");
end

function xml.sanitize(s)
    return (string.gsub(s, "[\"><'$]", {
        ["<"] = "&lt;",
        [">"] = "&gt;",
        ['"'] = "&quot;",
        ["'"] = "&apos;",
        ["$"] = ""
    }))
end

return xml;