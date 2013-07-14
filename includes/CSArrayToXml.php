<?php
class CSArrayToXML
{
	public static function arrayToXml($array, $root_node='root', $xml=null)
	{
		if($xml == null)
		{
			$xml = new DOMDocument("1.0", 'utf-8');
			$e = $xml->createElement($root_node);
			$xml->appendChild($e);
		}
		
		foreach($array as $key=>$value)
		{
			$node_name = '';
			if(is_numeric($key))
			{
				$node_name = 'node_' . $key;
			}
			else
			{
				$node_name = $key;
			}
			
			$e = $xml->createElement($node_name);
			if(is_array($value))
			{
				$e_child = CSArrayToXML::arrayToXml($value, 'root', $e);
				$e->appendChild($e_child);
			}
			else
			{
				$cdata = $xml->createCDATASection($value);
				$e->appendChild($cdata);
			}
			
			$xml->appendChild($xml);
		}
		
		return $xml;
	}
}
?>