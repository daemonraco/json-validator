# JSON Validator

## What is it?
This is a small PHP library useful to validate JSON structures.
This is similar to [DTD](https://en.wikipedia.org/wiki/Document_type_definition)
files for XML.

## How to use it
Here is an example of how to validate a JSON file stored at
`/path/to/json-to-validate.json`:
```php
<?php
// Loading the library.
require_once '/path/to/library/json-validator.php';
// Ferching a validator.
$validator = new JSONValidator('/path/to/json-specification.json');
// Validating...
$check = $validator->validatePath('/path/to/json-to-validate.json', $info);
// Showing results.
var_dump($check, $info);
```
You're probably wondering about `/path/to/json-specification.json` and what's
inside it, well, we'll talk about it in the next section.

## How to specify rules
Let's say you have a JSON file like this one:
```json
[
	{
		"name": "Flour",
		"description": "Useful to make bread",
		"price": 1.2,
		"type": "comestible",
		"status": "available"
	}, {
		"name": "Egg",
		"price": .73,
		"type": "comestible",
		"status": "soldout",
		"notes": "we've run out of these last week"
	}
]
```
If you want to make sure the it has the right structure you may specify a
structure like this one:
```json
{
	"types": {
		"Products": "Product[]",
		"Product": {
			"name": "+string",
			"description": "Description",
			"price": "+float",
			"type": "+string",
			"status": "ProductStatus",
			"notes": "Notes"
		},
		"ProductStatus": "/^(available|soldout)$/",
		"Notes": ["string", "object"],
		"Description": "string"
	},
	"root": "+Products"
}
```
yes, I know, it's confusing, let's explain it.

### Types of types
In you specification, the field `types` contains the list of all non primitive
types.
Each entry is a type name associated with it's actual definition that can be one
these types:

* _Alias_
* _List of types_
* _Structure_
* _Regular expression_

#### Alias
An alias time is just a redefinition of another type, or even expansion of another
type to convert it into a list of it.
I our example, `Products` is not only defined as an alias of `Product`, but a list
of entries that must follow the specification for `Product`.

`Description` is also an alias.

#### List of types
In our example, the type `Notes` is one of these.
Basically it tell us that any field of type `Notes` may be a `string` or an
`object`, but no other.

#### Structure
This is perhaps the complex one.
In it, each entry is a name of a field to look for associated with the type it
must follow.
In our example, `Product` is of this kind and it specifies six field, four of
primitive types and two of types we listed in our configuration.

Here, if you look closely, at least three fields have a plus sign (`+`) before its
type name, that flag indicates that it's a required field meaning the validation
will fail if not present.

#### Regular expression
The magical way is to specify a type as a [_regular
expression_](https://en.wikipedia.org/wiki/Regular_expression).
As you probably guest already, it will take a field and validate it against the
expression.

In our example, this means that product status can either be `available` or
`soldout`.

### Primitive types
By default, this library supports a set of type call primitives that perform
simple validations.
These types are:

* `array`: List of items that can be of any type.
* `boolean`: Either `true` or `false`.
* `float`: Floating point number.
* `int`: An integer.
* `mixed`: Any type.
* `object`: Structured type.
* `string`: A simple string.

### Containers
Previously we mention the type `Products` and that it's an alias representing a
list of products, _but how?_

When we specify a type as an alias we may use `[]` or `{}` at the end of the
type's name to indicate that it's a container of.
When using `[]` we are saying that it's a simple list of items and `{}` means that
it's an associative list.

### Root type
If out look at the end of our example, there's a field called `root`, this will be
the first type to check.
Everything has to start somewhere, right? :D
