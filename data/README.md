# JSON Data
Data stored in JSON files are used by the client-side script to show the list of emoji, display their translated names
and search and filter them by their (internationalised) keywords.

JSON files are generated from the Unicode CLDR XML files.

## Groups
File named `groups.json` contains list of groups in order in which they should be sorted and the list of emoji belonging
into each group.

Object `groups` should contain ordered list of objects representing groups. Each group should define:
* `id` defining string or number under which its translation can be found in `emoji.xx.json` in `groups` object,
* `list` defining ordered array of strings containing an emoji (one string for each emoji) or objects representing groups.
   Name of the key can be shortened as `l` to save data in JSON. 
```
//groups.json example
{
	"groups": [
		{
			"id": "smileys_people",
			"list": [
				{
					"id": "face-positive",
					"list": [
						"☺",
						"\uD83D\uDE00",
						"\uD83D\uDE01",
						"\uD83D\uDE02",
						"\uD83D\uDE03",
						"\uD83D\uDE04",
						"\uD83D\uDE05",
						"\uD83D\uDE06"
					]
				}
			]
		}
	]
}
```

Note: we could use objects instead of arrays, but in JSON the object is unordered by specification which means the order
of the keys can be randomly changed. To keep order of the elements we need to use array which is always ordered in JSON.
The above-used structure is based on Java's LinkedHashArray which can keep both order and names (key or hash) of values.

## Emoji
Files named `emoji.xx.json` contain list of emoji characters and their names and keywords in given language.
Also contains list of groups and their translations in given language.

Each `emoji.xx.json` file should define objects `emoji` and `groups`.

Object `emoji` should have keys defined in `groups.json` file in `list` properties as strings. Each key should contain
an object with properties:
* `name` defining translated name of the emoji
* `modifier` defining modificator of the name (e.g. skin type, gender, etc.) 
* `keywords` with translated list of words (separated by pipe `|`) that match the emoji when searched. Keywords already contained in `name` does
  not have to be listed to save data in JSON. The client must search keywords both in `name` and `keywords`.
* `type` defining UNICODE type of the emoji (basic, modifier, ZWJ, etc.)
 
All properties are options. When only `keywords` are known for an emoji (i.e. there is no `name` or other properties), instead of an object the key
can contain string with the keywords.

Emoji with empty key (`""`) can define translated `name` for emoji without own name.

Property names `name`, `modifier`, `keywords` and `type` can be shortened to `n`, `m`, `k` and `t` respectively.
The value of `type` can be also shortened as defined in this list:
* `Emoji_Keycap_Sequence` as `k`,
* `Emoji_Flag_Sequence` as `f`,
* `Emoji_Tag_Sequence` as `t`,
* `Emoji_Modifier_Sequence` as `m`,
* `Emoji_ZWJ_Sequence` as `j`,
* Basic_Emoji is default type and emoji of this type does not have to have the `type` defined.

Object `groups` should have keys defined in `groups.json` file in `id` properties. Each key should contain an object with
properties:
* `name` specifying translated name of that group.

When only a name is known (i.e. there are no other properties), instead of an object the key can contain a string with
the translation.

```
//emoji.en.json example
{
	"emoji": {
		"": {
			"name": "Unknown"
		},
		"☺": {
			"name": "Smiling face",
			"keywords": " face | outlined | relaxed | smile | smiling face "
		},
		"😀": {
			"name": "Grinning face",
			"keywords": "face | grin | grinning face"
		},
		"1\u20e3": {
			"name": "Keycap",
			"modifier": "1",
			"type": "Emoji_Keycap_Sequence",
		}
	},
	"groups": {
		"smileys_people": {
			"name": "Smileys & People"
		}
	}
}
```

Note that based on JSON specification all UTF-8 characters must be encoded as \uXXXX. 

## TODO
_How to define variants (e.g. gender or skin-color) of each emoji to create filter?_

# Emoji list

Unicode organization defines and maintains list of all characters defined world wide (e.g. latin letter, cyrillic
letters, Chinese/Kanji characters, etc.).
 
Special group in these characters are _emoji_ (from japanese either combination
of "e" (_picture_) and "moji" (_letters_) or "emo" (from english "_emotions_") and "ji" (_language_).

Each year Unicode organization releases new version of emoji extended by the current needs.

Currently latest released version is Emoji 12.1 (2019); currently working on Emoji 13 for year 2020.    

List of supported emoji can be found in file https://unicode.org/Public/emoji/12.1/emoji-sequences.txt
(for version Emoji 12; use respective newer version as needed)

## ZWJ (Zero Width Joiner)
Some emoji may contain ZWJ (read "zwidge"), a hidden character (`U+200D`) that marks that the emoji must be displayed  
differently.

For example emoji for a male or female followed by ZWJ and emoji for hospital should be displayed as a male or female  
doctor respectively. For another example any hand or person emoji followed by ZWJ and skin tone emoji means the first  
emoji must be displayed with given skin tone. Another possibility is a person emoji followed by ZWJ and hair color emoji
(e.g. red hair) which will change color of hair on the emoji.

The above examples can be combined to create specific emoji. For example "Dark skin tone male doctor with black hair"
consist of emoji hospital, male, dark skin and black hair (theoretically because such emoji is not supported yet).

List of supported emoji that uses ZWJ is in file https://unicode.org/Public/emoji/13.0/emoji-zwj-sequences.txt
(for version Emoji 13; use respective newer version as needed)

Note that some emoji may use modifier without ZWJ. For example most of the emoji representing people (man, woman, etc.),
actions (running, swimming, etc.) or body parts (hands, nose, ear, etc.) can use skin tone modifier without the ZWJ
simply by appending the skin tone emoji after the basic emoji (e.g. Santa Claus consists of 2 unicode characters:
santa + skin tone).
However in these cases the basic emoji cannot be displayed as standalone emoji and is required to contain skin tone
even for the basic (medium skin tone) version. These emoji are listed in the basic emoji sequence list
in group `Emoji_Modifier_Sequence` (see above). 

## Variantion selector
Emoji may contain hidden character `U+FE0F`. This character means that the previous character is normally not considered
emoji but here should be considered a part of the previous emoji (or create an emoji with the preceding character)
for which it defines a variant.
e.g. square symbol is not part of emoji list, however when printed after a number followed by `U+FE0F` it should
be displayed as "_a number in square_" emoji (_Keycap_).

On the other hand, if an emoji is followed by hidden character `U+FE0E` it means that the emoji should be displayed as
its text representation. Usually this means that the icon will display only in black&white and for some characters
it will display simpler version of the symbol. In documents this can be used to be able to format the emoji as same as
text (i.e. apply text color to the symbol). Also text representation of an emoji should have same size as normal text
so it can useful when it is unwanted that line height would be increased in order to display larger emoji.

List of supported variantion emoji is listed in file:
https://unicode.org/Public/13.0.0/ucd/emoji/emoji-variation-sequences.txt
(for version Emoji 13; use respective newer version as needed)

# Core Data
Core data are from _Unicode Common Locale Data Repository_.

_Data not related to emoji has been removed from this repository._

The Core package of CLDR contains:
A) list of all emoji characters defined by the UNICODE organization, their names, categories and keywords and
B) translations for all the texts collected from various Organizations (goverments, companies and other national sources).

Find the latest version on http://cldr.unicode.org/.

Currently included version is http://unicode.org/Public/cldr/36/core.zip

These data are not required to run the SmartEmoji, they are only used to prepare the JSON files used by it.

# CLDR help
## Annotations
Contains translated names and keywords of all basic emoji symbols.

Attribute `cp` of each annotation define the emoji for the record. Annotation with attribute `type=tts` defines the
human-readable name of the emoji. The annotation without this attribute defines a list of keywords that describe
the emoji.

## AnnotationsDerived
Contains translated names and keywords of all emoji that serve as variants of the basic set. Derived emoji include
emoji with different skin color or various genders and their combination (e.g. emoji "_light-skin toned woman kissing
dark-skin toned man_").

Derived emoji consist of a set of basic emoji and each font that support emoji must know how to compile the final image.
For example the above emoji consists of 6 basic emoji: _woman, light-skin, heart, mouth, man, dark-skin_.

## Properties - Labels
File `labels.txt` contains lists of emoji sorted by their categories (e.g. "_Smileys - Positive faces_").

These lists can be used to generate the Emoji picker with emoji ordered in their relevant order.

Each list (row) contains list of emoji in square brackets (array). Each UTF-8 character in the list is one emoji.
A dash character (`-`) means that all UTF-8 characters between the left and right character belong into this list.
UTF-8 characters in curly brackets (`{}`) define derived emoji and must be considered as one emoji.

Category (label) and sub-category (second-level label) of the list is separated by a semi-colon (`;`) and
optional white-spaces.

Category _Smileys & People - skin-tone_ lists variants for derived emoji and should be used for variant selection.

Translations for Categories can be found in `main` XMLs under `characterLabels`. To get the label type a category its
name must be convert: make all letters lower-case, remove all white-spaces and replace "`&`" with "`_`".
e.g. translation for group "_Smileys & People_" can be found in `characterLabel` with `type=smileys_people`.

## Collations
Collations define how UNICODE characters should be sorted in general (file `root.xml`) and in each language (nationalized
XMLs). Specifically for emoji the collation of `type=emoji` defines in which order should be the emoji sorted.

Each Collation node contains CDATA with sorting rule definitions:
* It does not matter if a rule is defined on separate row or in a single row. Characters are always ordered left-to-right
  regardless of line separators.
* `&` before a character defines start of sorting rule meaning all following characters should be sorted after this one.
   Each character can be listed multiple times with `&` which means the following rules should be added into the order
   list.
   e.g. "`& a`" means the letter `A` is to be the first in order and all other should follow it.
* `<` before a character means that this character should be sorted after the previous one.
   Multiple `<` means that the character should be sorted before other characters with less `<` in their rule.
   e.g. "`& a < b & a << ä & a <<< A`" defines sorting order "`a A ä b`" (i.e. `b` follows `a` and all its variants).
* `=` means that these two characters are equal and they can be sorted either way.
   e.g. "`& v = w`" means the letters `V` and `W` are equal and can be ordered either `vw` or `wv`.
* List of characters after any above rule definer means that they must be considered and sorted as one character.
   e.g. "`& h < ch`" means that `ch` is considered single character that should be sorted after `h`.
        "`& s < ss = ß`" means that `ss` is equal to `ß` and should be sorted after the letter `s`.
* `*` after any above rule definer means that each character in the list is to be considered a separate character
  sorted in given order with the same importance.
   e.g. "`& a <<\* äáâ`" equals to "`& a << ä << á << â`" and means that `ä`, `á` and `â` must be ordered after `a`.
* When a character that is already in the list is listed again after `<` or `=` it means it must be moved in the list.

# Code pages
Here is a short list of code pages and their history explaining what UNICODE is used nowadays.

* Old telegraph communication (in 19th and 1st half of 20th century) was using **5bit** or **6bit** code pages (as an
  alternative to morse code) based on the needs of national alphabet (e.g. French ITA 2).
* In 1967 **ASCII** (_American Standard Code for Information Interchange_) was defined as **7bit** code page used in the
  first computers. Extension to ASCII is UTF-7 which uses 7bit set to describe characters from ANSI and UNICODE.
  7bit code page is still required in some forms of communication such as emails or JSON (where UTF-7 or `\uXXXX` must
  be used to describe 8bit or UNICODE characters).
* In code page terminology the **ANSI** (comes from _American National Standards Institute_) is a term used for **8bit**
  code pages; originally used for US code pages but later also for _any nationalized_ code page.
  There are lots of code pages ANSI can refer to, such as OEM (cp437) used in 1981's IBM PC,
  Windows code pages 125x used by nationalized Windows OS or ISO-8859, a set of 15 different sets of nationalized
  lists of characters (which are fully backward compatible with ASCII).
* **UNICODE 1.0** (developed since 1980) is a **16bit** code page that contains all characters from all previously
  released nationalized code pages and some additional characters (such as Klingon script from Star Trek).
  UNICODE 1.0 contains 63.5 thousands of characters (65535 - 2047 code points in range D800 - DFFF reserved for UTF-16).
  Unicode characters can be written as 2 bytes (based on byte order either Big-endian or Little-endian) referred as
  UCS-2 (Universal Character Set with 2 bytes) or simply UNICODE or in UTF-8 (rarely referred as MB3) which encodes
  ASCII as one byte, ANSI as 2 bytes and other UNICODE characters as 3 bytes (simply said).
* **UNICODE** in modern form (for 21st century; published in 2000) is **32bit** code page that includes all world-wide
  used characters such as emoji. There is approximately 1.1 million (U+10FFFF) UNICODE characters.
  UNICODE nowadays is usually written in extended UTF-8 (used in Linux-based and web-based systems; sometimes is
  referred as MB4, for example in MySQL) which can contain more that 3 bytes per character, UTF-16 (which is 
  the UCS-2 (UNICODE) extended to use 2 bytes for 16bit characters and 4 bytes for 32bit characters;
  currently used internally by Windows, Java and Javascript for in-memory strings and filenames),
  or UTF-32 which writes each character as 4 bytes. 
