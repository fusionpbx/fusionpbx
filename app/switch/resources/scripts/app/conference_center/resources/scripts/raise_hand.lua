--options are true and false and toggle
	data = argv[1];
	if (data == "toggle") then
		data = session:getVariable("hand_raised")
		if(data == "true")then
			session:setVariable("hand_raised","false")
		else
			session:setVariable("hand_raised","true")
		end
	elseif(data == "true")then
		session:setVariable("hand_raised","true")
	elseif(data == "false")then
		session:setVariable("hand_raised","false")
	end

