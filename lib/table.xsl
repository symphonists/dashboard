<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output encoding="UTF-8" indent="yes" method="html" />

<xsl:template match="/">
	
	<xsl:variable name="columns" select="//entry[1]/*"/>
	
	<table>
		<caption><xsl:value-of select="//section"/></caption>
		<thead>
			<tr>
				<xsl:for-each select="$columns">
					<th><xsl:value-of select="name()"/></th>
				</xsl:for-each>
			</tr>
		</thead>
		<tbody>
			<xsl:for-each select="//entry">
				<tr>
					<xsl:variable name="entry" select="."/>
					<xsl:for-each select="$columns">
						<td><xsl:value-of select="$entry/*[name()=name(current())]"/></td>
					</xsl:for-each>
				</tr>
			</xsl:for-each>			
		</tbody>
	</table>
</xsl:template>

</xsl:stylesheet>
