local pattern = '^%x%x%x%x%x%x%x%x%-%x%x%x%x%-%x%x%x%x%-%x%x%x%x%-%x%x%x%x%x%x%x%x%x%x%x%x$'

function is_uuid(s)
  return not not string.match(s, pattern)
end

--[[
local function is_uuid_self_test()
  print('Is UUID self test ...')
  local pass_tests = {
    '34dd925b-f320-425f-ad87-0573c5b853c8',
    '34DD925B-F320-425F-AD87-0573C5B853C8',
  }
  for _, value in ipairs(pass_tests) do
    assert(true == is_uuid(value), value)
  end

  local fail_tests = {
    -- no some digints
    '4dd925b-f320-425f-ad87-0573c5b853c8',
    '34DD925B-320-425F-AD87-0573C5B853C8',
    '34dd925b-f320-425f-d87-0573c5b853c8',
    '34DD925B-F320-425F-AD87-573C5B853C8',
    '034dd925b-f320-425f-ad87-0573c5b853c8',
    '34DD925B-0F320-425F-AD87-0573C5B853C8',
    '34dd925b-f320-0425f-ad87-0573c5b853c8',
    '34DD925B-F320-425F-0AD87-0573C5B853C8',
    '34dd925b-f320-425f-ad87-00573c5b853c8',
    ' 34DD925B-F320-425F-AD87-0573C5B853C8',
    '34DD925B-F320-425F-AD87-0573C5B853C8 ',
    'G4DD925B-F320-425F-AD87-573C5B853C8',
  }
  for _, value in ipairs(fail_tests) do
    assert(false == is_uuid(value), value)
  end
  print('Is UUID self test - pass')
end

is_uuid_self_test()
--]]