/* -- BEGIN LICENSE BLOCK ----------------------------------
 * This file is part of kUtRL, a plugin for Dotclear 2.
 * 
 * Copyright (c) 2009-2011 JC Denis and contributors
 * jcdenis@gdwd.com
 * 
 * Licensed under the GPL version 2.0 license.
 * A copy of this license is available in LICENSE file or at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -- END LICENSE BLOCK ------------------------------------*/

$(function() {
	var kutrlForm=$('#setting-form');
	if (kutrlForm!=undefined){
		dotclear.jcTools.formFieldsetToMenu(kutrlForm);
	}
});