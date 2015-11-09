<?xml version="1.0" encoding="UTF-8"?>
 
<!--
    Document   : phpcs.xsl
    Created on : December 27, 2010, 1:42 PM
    Author     : schkovich
    Description:
        Transformation PHP_CodeSniffer xml report into human readable format.
-->
 
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:output method="html"  encoding="UTF-8"/>
 
    <!-- TODO customize transformation rules
         syntax recommendation http://www.w3.org/TR/xslt
    -->
    <xsl:template match="/">
        <html>
            <head>
                <title>phpcs.xsl</title>
                <link href="./codereview.css" rel="stylesheet" type="text/css" />
            </head>
            <body>
                <input type="checkbox" checked="checked" onchange="document.getElementById('table1').className=this.checked?'only-new':''"/> Only new issues
                <table id="table1" class="only-new">
                    <thead>
                        <tr>
                            <th class="file">Name</th>
                            <th class="notes">Errors</th>
                            <th class="notes">Warnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:for-each select="phpcs/file">
                            <tr class="header">
                                <td class="file">
                                    <xsl:value-of select="@name" />
                                </td>
                                <td class="errors">
                                    <xsl:value-of select="@diff_errors" /> / <xsl:value-of select="@errors" />
                                </td>
                                <td class="warnings">
                                    <xsl:value-of select="@diff_warnings" /> / <xsl:value-of select="@warnings" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <xsl:for-each select="error">
                                        <div class="@is_new='true'">
                                            <xsl:if test="@is_new='true'" >
                                                (NEW)
                                            </xsl:if>
                                            <span class="error">Error: </span>
                                            <xsl:value-of select="self::node()"/>
                                         
 
                                            <b> Line:</b>
                                            <xsl:value-of select="@line" />
                                         
 
                                            <b> Column:</b>
                                            <xsl:value-of select="@column" />
                                         
 
                                            <b> Code:</b>
                                            <xsl:value-of select="@source" />
                                            <hr />
                                        </div>
                                    </xsl:for-each>
                                    <xsl:for-each select="warning">
                                        <xsl:if test="@is_new='true'">
                                            (NEW)
                                        </xsl:if>
                                        <span class="warning">Warning: </span>
                                        <xsl:value-of select="self::node()"/>
                                         
 
                                        <b> Line:</b>
                                        <xsl:value-of select="@line" />
                                         
 
                                        <b> Column:</b>
                                        <xsl:value-of select="@column" />
                                         
 
                                        <b> Code:</b>
                                        <xsl:value-of select="@source" />
                                        <hr />
                                    </xsl:for-each>
                                </td>
                            </tr>
                        </xsl:for-each>
                    </tbody>
                </table>
                <div class="errors-warnings">
                    <xsl:value-of select="$errorsWarnings" />
                </div>
            </body>
        </html>
    </xsl:template>
 
</xsl:stylesheet>
