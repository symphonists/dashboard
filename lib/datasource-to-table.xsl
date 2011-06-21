<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output encoding="UTF-8" indent="yes" method="html" />

<xsl:variable name="columns" select="//entry[1]/*"/>

<xsl:template match="/">
	<table class="skinny">
		<thead>
			<tr>
				<xsl:apply-templates select="$columns" mode="th"/>
			</tr>
		</thead>
		<tbody>
			<xsl:apply-templates select="//entry" mode="tr"/>
		</tbody>
	</table>
</xsl:template>

<xsl:template match="*" mode="th">
	<th><xsl:value-of select="name()"/></th>
</xsl:template>

<xsl:template match="entry" mode="tr">
	<tr>
		<xsl:if test="position() mod 2 = 0">
			<xsl:attribute name="class">alt</xsl:attribute>
		</xsl:if>
		<xsl:apply-templates select="$columns" mode="td">
			<xsl:with-param name="entry" select="."/>
		</xsl:apply-templates>
	</tr>
</xsl:template>

<xsl:template match="*" mode="td">
	<xsl:param name="entry"/>
	
	<xsl:variable name="field" select="$entry/*[name()=name(current())]"/>
	
	<td>
		<xsl:attribute name="class">
			<xsl:choose>
				<xsl:when test="$field/@word-count or $field[@mode='formatted'] or $field[@mode='unformatted']">
					<xsl:text>textarea</xsl:text>
				</xsl:when>
				<xsl:when test="$field/@time">
					<xsl:text>date</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>text</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		
		<xsl:choose>
			<xsl:when test="$field/item/@section-handle">
				<a href="/symphony/publish/{$field/item/@section-handle}/edit/{$field/item/@id}/">
					<xsl:value-of select="$field"/>
				</a>
			</xsl:when>
			<xsl:when test="position()=1">
				<a href="/symphony/publish/{//section/@handle}/edit/{$entry/@id}/">
					<xsl:value-of select="$field"/>
				</a>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="truncate">
					<xsl:with-param name="text" select="$field"/>
					<xsl:with-param name="length" select="100"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
		
	</td>
	
</xsl:template>

<xsl:template name="truncate">
	<xsl:param name="text"/>
	<xsl:param name="length"/>
	
	<xsl:choose>
		<xsl:when test="string-length($text) &gt; $length">
			<xsl:value-of select="substring($text, 0, $length)"/>
			<xsl:text>...</xsl:text>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="$text"/>
		</xsl:otherwise>
	</xsl:choose>
	
</xsl:template>

</xsl:stylesheet>
