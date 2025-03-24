const targetText = "فدرت گرفته از جدول لیگ آزادگان ورزش سه";
const targetTagName = "a";

/*
* By default, this JS snippet finds the element by an exact match.
* If you want to use a partial match, replace the xpath expression with following.
* //${targetTagName}[contains(text(), "${targetText}")]
*/

const xpath = `//${targetTagName}[text()="${targetText}"]`;

function getElementByXpath(xp) {
  return document.evaluate(
    xp,
    document,
    null,
    XPathResult.FIRST_ORDERED_NODE_TYPE,
    null
  ).singleNodeValue;
}

const element = getElementByXpath(xpath);

if (!element) {
  throw new Error(`Error: Element not found (${xpath})`);
}

/*
* Write some interactions with the element.
* Use the click() method, put assertions, just return it, etc. 
*/
element.innerText = 'Your Suggestions';
