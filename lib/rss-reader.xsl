<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output encoding="UTF-8" indent="yes" method="html" />

<xsl:template match="/">
	
	<xsl:choose>
		<xsl:when test="$show='all'">
			<xsl:apply-templates select="//item"/>
		</xsl:when>
		<xsl:otherwise>
			<xsl:apply-templates select="//item[position() &lt;= $show]"/>
		</xsl:otherwise>
	</xsl:choose>
	
</xsl:template>

<xsl:template match="item">
	
	<h5>
		<a href="{link}">
			<xsl:value-of select="title"/>
		</a>
	</h5>
	
	<p class="date">
		<xsl:value-of select="pubDate"/>
	</p>
	
	<xsl:choose>
		<xsl:when test="description/*">
			<xsl:copy-of select="description/*"/>
		</xsl:when>
		<xsl:otherwise>
			<p><xsl:value-of select="description" disable-output-escaping="yes"/></p>
		</xsl:otherwise>
	</xsl:choose>
	
</xsl:template>

</xsl:stylesheet>
