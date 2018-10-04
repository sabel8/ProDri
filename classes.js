//NODE CLASS
//constructor of the Node element
class Node {
	constructor(ID, txt, x, y, status, knowledgeArea, responsiblePerson, duration, RACI){
		this.ID = ID;
		this.txt = txt;
		this.x = x;
		this.y = y;
		this.knowledgeArea = knowledgeArea;
		this.responsiblePerson = responsiblePerson;
		this.duration = duration;
		this.RACI = RACI;
		this.output = 0;
		this.input = 0;
		// 0 : not yet started
		// 1 : in progress
		// 2 : done
		if (status==null){
			this.status = 0;
		} else {
			this.status = status;
		}

		this.toString = function(){
			return "ID: "+this.ID+"; text: "+this.txt+"; x: "+this.x+"; y: "+y
			+"; status: "+this.status+"; output: "+this.output+"; input: "
			+this.input+"; knowledgeArea: "+this.knowledgeArea+"; res.person: "
			+this.responsiblePerson+"; duration: "+this.duration+"; RACI: "+this.RACI;
		}
	}

	getColor(){
		let color; 
		switch(this.status) {
			case 0:
			color = "#ff3333";
			break;
			case 1:
			color = "lightblue";
			break;
			case 2:
			color = "#33cc33";
		}
		return color;
	}

	getStatus(){
		let text; 
		switch(this.status) {
			case 0:
			text = "Not yet started";
			break;
			case 1:
			text = "In progress";
			break;
			case 2:
			text = "Done";
		}
		return text;
	}
}

//seperates the text into words for readability
function insertText(gEl, id, title) {
	//returns if no valid title passed
	if(title==null){
		return;
	}

	//initialize the text element
	var el = gEl.append("text")
	.attr("text-anchor","middle")
	.attr("dy", "-7.5");

	el.append("tspan").html("ID: ").style("font-weight","bold");
	el.append("tspan").text(id);
	el.append("tspan").html("Name: ").style("font-weight","bold").attr('x', 0).attr('dy', '20');
	el.append("tspan").text(title);
};

function getNodeByID(nodeID) {
	for (var i = 0;i<nodes.length;i++) {
		if (nodes[i].ID==nodeID) {
			return nodes[i];
		}
	}
	return null;
}

//EDGE CLASS
class Edge {
	constructor(ID,fromNodeID,toNodeID) {
		this.ID = ID;
		this.fromNodeID = fromNodeID;
		this.toNodeID = toNodeID;
		this.toString = function(){
			return "ID: "+this.ID+"; fromNodeID: "+this.fromNodeID
			+"; toNodeID: "+toNodeID;
		}
	}

}

//returns the array index of the element
//the element must have an ID property
function getPosInArray(objectID,array) {
	for (var i = 0;i<array.length;i++) {
		let curObject = array[i];
		if (curObject.ID===objectID) {
			return i;
		}
	}
	return null;
}

//index: possible ID
//array: the array of the other elements
function getValidID(array) {
	for (var index = 0;index<10000;index++) {
		var wrongID = false;
		for (var i = 0;i<array.length;i++) {
			if (array[i].ID===index) {
				wrongID=true;
				break;
			}
		}
		if (wrongID===true) {
			continue;
		}
		return index;
	}
}

function getPath(d){
    var from = getNodeByID(d.fromNodeID);
    var to = getNodeByID(d.toNodeID);
    var recW = rectWidth;
    var recH = rectHeight;
    var recR = rectRoundness;

    //case 1
    if (from.x-recW < to.x && to.x < from.x+recW && from.y > to.y){
        return "M"+from.x+","+(from.y-(recH/2))+
        "L"+to.x+","+(to.y+(recH/2));
    }
    //case 2
    else if (to.x<=from.x-recW && to.y<=from.y-recH) {
        return "M"+(from.x-(recW/2)+recR)+","+(from.y-(recH/2)+recR)+
        "L"+(to.x+(recW/2)-(recR*0.2))+","+(to.y+(recH/2)-(recR*0.2));
    }
    //case 3
    else if(to.x<from.x && (from.y-recH < to.y) && (to.y < from.y+recH)) {
    	return "M"+(from.x-(recW/2))+","+from.y+
    		"L"+(to.x+(recW/2))+","+to.y;
    }
    //case 4
    else if(to.x<=from.x-recW && from.y+recH<=to.y) {
    	return "M"+(from.x-(recW/2)+recR)+","+(from.y+(recH/2)-recR)+
    	"L"+(to.x+(recW/2)-(recR*0.2))+","+(to.y-(recH/2)+(recR*0.2));
    }
    //case 5
    else if (from.x-recW<to.x && to.x<from.x+recW && to.y>from.y) {
    	return "M"+from.x+","+(from.y+(recH/2))+
    		"L"+to.x+","+(to.y-(recH/2));
    }
    //case 6
    else if (from.x+recW<=to.x && from.y+recH<=to.y) {
    	return "M"+(from.x+(recW/2)-recR)+","+(from.y+(recH/2)-recR)+
    		"L"+(to.x-(recW/2)+(recR*0.2))+","+(to.y-(recH/2)+(recR*0.2));
    }
    //case 7
    else if (from.x<to.x && from.y-recH<to.y && to.y<from.y+recH) {
    	return "M"+(from.x+(recW/2))+","+from.y+
    		"L"+(to.x-(recW/2))+","+to.y;
    }
    //case 8
    else if (from.x+recW<=to.x && from.y-recH>=to.y) {
    	return "M"+(from.x+(recW/2)-recR)+","+(from.y-(recH/2)+recR)+
    		"L"+(to.x-(recW/2)+(recR*0.2))+","+(to.y+(recH/2)-(recR*0.2));
    } else {
    	alert("error");
    }
}