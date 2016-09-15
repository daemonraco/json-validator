# JSON Validator

## What is it?
This is a small PHP library useful to validate JSON structures.

## How to specify rules
```json
{
	"root": "+Type_1",
	"types": {
		"Type_1": "Type_2[]",
		"Type_2": "Type_3",
		"Type_3": ["string", "int", "Type_4"],
		"Type_4": {
			"name": "+string",
			"description": "string",
			"age": "+int"
		}
	}
}
```
