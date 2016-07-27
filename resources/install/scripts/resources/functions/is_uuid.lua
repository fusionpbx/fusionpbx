function is_uuid(s)
  if (string.len(s) == 36) then
    local x = "%x";
    local t = { x:rep(8), x:rep(4), x:rep(4), x:rep(4), x:rep(12) }
    local pattern = table.concat(t, '%-');
    result = s:match(pattern);
  end
  if (result == nil) then
    return false;
  else
    return true;
  end
end
