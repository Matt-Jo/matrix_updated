function createProductAddon(parentId, childId, updateTableId, displayId){

	addArgs = [parentId, childId, updateTableId, displayId];
	var callback = {
			success: handleAddSuccess,
			failure: handleAddFailure,
			argument: addArgs
	};
	var url = "pa_addProductAddon.php?parentId=" + parentId + "&childId=" + childId + "&displayId=" + displayId;
	
	YAHOO.util.Connect.asyncRequest('GET', url, callback);
}

var handleAddSuccess = function (o){
	
	eval(o.responseText);
	
}

var handleAddFailure = function (o){
	if(o.responseText !== undefined){
		alert("Add product option failed: " + o.responseText);
	}
	else{
		alert("Add product option failed: no error message available");
	}
}

var addArgs = null;

function removeProductAddon(parentId, childId, updateTableId, removeRowId){

	if (!confirm("Are you sure you want to remove this option?")){
		return false;
	}

	removeArgs = [parentId, childId, updateTableId, removeRowId];
	var callback = {
			success: handleRemoveSuccess,
			failure: handleRemoveFailure,
			argument: removeArgs
	};
	var url = "pa_removeProductAddon.php?parentId=" + parentId + "&childId=" + childId;

	YAHOO.util.Connect.asyncRequest('GET', url, callback);
}

var handleRemoveSuccess = function (o){

	var removeRowId = o.argument[3];
	var rowElem = document.getElementById(removeRowId);
	var parentElem = rowElem.parentNode;
	parentElem.removeChild(rowElem);

}

var handleRemoveFailure = function (o){
	if(o.responseText !== undefined){
		alert("Remove product option failed: " + o.responseText);
	}
	else{
		alert("Remove product option failed: no error message available");
	}
}

var removeArgs = null;