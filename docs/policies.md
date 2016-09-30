# JSON Validator: Policies

## Policies
_JSON Validator_ policies are a set of rules that can be defined on our types
limiting their possible values to something more controlled.
For example, if you defined a type that holds an integer value, you may want to
limit its value to be between `1` and `10` (both inclusive), except `6`,  and
reject the rest.

Following this example, you may create a specification like this one:
```json
{
        "root": "Root",
        "types": {
                "Root": {
                        "somefield": "+LimitedInt"
                },
                "LimitedInt": "int"
        },
        "policies":{
                "LimitedInt": {
                        "min": 1,
                        "max": 10,
                        "except": [6]
                }
        }
}
```

Something to consider here is that you can only specify policies to named types,
in other words, you can't add a policy, for example, to `int` or `string`.
Also, not all types will have policies and not all will have the same policies,
but we'll talk about that in further sections.

## Policies for primitive types
This is the list of primitive types that support policy definition in _JSON
Validator_:

* `array`:
 * `except`: List of values that cannot be present.
 * `max`: Maximum amount of element.
 * `min`: Minimum amount of element.
 * `only`: List of allowed values.
* `float`:
 * `except`: List of values that cannot be present.
 * `max`: Maximum value (inclusive).
 * `min`: Minimum value (inclusive).
 * `only`: List of allowed values.
* `int`:
 * `except`: List of values that cannot be present.
 * `max`: Maximum value (inclusive).
 * `min`: Minimum value (inclusive).
 * `only`: List of allowed values.
* `string`:
 * `except`: List of values that cannot be present.
 * `max`: Maximum length.
 * `min`: Minimum length.
 * `only`: List of allowed values.

## Policies for containers
Types specified as containers of another may have these policies if they work as
arrays:

* `max`: Maximum amount of element.
* `min`: Minimum amount of element.

For example:
```json
{
        "root": "Root",
        "types": {
                "Root": {
                        "somefield": "+LimitedIntList"
                },
                "LimitedIntList": "int[]"
        },
        "policies":{
                "LimitedIntList": {
                        "min": 1,
                        "max": 10
                }
        }
}
```

## Policies for structures
Structured types allow only the policy `strict` that protect it from undefined
fields.
For example, you may have this:
```json
{
	"root": "Root",
	"types": {
		"Root": {
			"required": "+int",
			"optional": "int"
		}
	},
	"policies": {
		"Root": {
			"strict": true
		}
	}
}
```
This implies that you need a field called `required` and you may or may not have a
field called `optional`, but no other.
