<?php

namespace SoftDreams\LaravelVuexCrud\Traits;

trait TabIndenter
{
	public static $tab_chars = '    ';
	public static $line_ending = "\n";
	public static function tabIndent($text , $no_tabs)
	{
		$text_lines = explode("\n" , $text);
		foreach($text_lines as $index => $line)
		{
			$add_tab_chars = '';
			for ($i = 0 ; $i < $no_tabs ; $i++)
			{
				$add_tab_chars .= static::$tab_chars;
			}

			$text_lines[$index] = $add_tab_chars . $line;
		}

		return implode(static::$line_ending , $text_lines);
	}
}
