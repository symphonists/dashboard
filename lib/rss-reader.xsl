<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output encoding="UTF-8" indent="yes" method="html" />

<xsl:template match="/">
	
	<xsl:choose>
		<xsl:when test="error">
			<p class="invalid"><xsl:value-of select="error"/></p>
		</xsl:when>
		<xsl:when test="$show='full-all'">
			<xsl:apply-templates select="//item" mode="full"/>
		</xsl:when>
		<xsl:when test="contains($show,'full')">
			<xsl:apply-templates select="//item[position() &lt;= substring-after($show,'-')]" mode="full"/>
		</xsl:when>
		<xsl:when test="$show='list-all'">
			<ul>
				<xsl:apply-templates select="//item" mode="list"/>
			</ul>
		</xsl:when>
		<xsl:when test="contains($show,'list')">
			<ul>
				<xsl:apply-templates select="//item[position() &lt;= substring-after($show,'-')]" mode="list"/>
			</ul>
		</xsl:when>
	</xsl:choose>
	
</xsl:template>

<xsl:template match="item" mode="list">
	
	<li>
		<a href="{link}"><xsl:value-of select="title"/></a>
		<span class="date"><xsl:value-of select="pubDate"/></span>
	</li>
	
</xsl:template>

<xsl:template match="item" mode="full">
	
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
