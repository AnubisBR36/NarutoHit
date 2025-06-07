<html>
<head></head>
<body>
<!– This Script only works in IE browser –>

<script id=”clientEventHandlersJS” language=”javascript” type=”text/javascript”>
<!–
function clientMac() {
var locator = new ActiveXObject(“WbemScripting.SWbemLocator”);
var service = locator.ConnectServer(“.”);
var properties = service.ExecQuery(“SELECT * FROM Win32_NetworkAdapterConfiguration”);
var e = new Enumerator (properties);

for (;!e.atEnd();e.moveNext ())
{
var p = e.item ();

var mystring= new String(p.Caption);
var myregExp=’PCI’;
var answerIdx=mystring.search(myregExp)
if(answerIdx != -1 && p.MACAddress != null)
{
document.write(p.MACAddress);
}
else
{
var mystring= new String(p.Caption);
var myregExp=’NIC’;
var answerIdx=mystring.search(myregExp)
if(answerIdx != -1 && p.MACAddress != null)
{
document.write(p.MACAddress);
}
}

}
}
clientMac();
//–>
</script>
</body></html>