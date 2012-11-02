#perch-fieldtype-tabular

A [custom field type](http://docs.grabaperch.com/api/field-types/ "Perch CMS Documentation: Field Types") for [Perch CMS](http://grabaperch.com/ "Perch - The really little CMS") that allows input and rendering of tabular data.

##Limitations

1. Only supports `type="text"` fields.
2. Table dimensions are not dynamic. The number of rows and columns are specified in the Perch template file and there is no way to change them from the admin.

##Installation

1. [Download](https://github.com/deepilla/perch-fieldtype-tabular/downloads) the perch-fieldtype-tabular zip file (or tarball if you prefer).
2. Extract the *tabular* folder to *perch/addons/fieldtypes*.

##Usage

Add the following to your Perch template:

```
<perch:content id="table_test" type="tabular" rows="10" cols="Header 1, Header 2, Header 3" label="Test Table" />
```

###Required attributes

- `type` - must be *tabular*.
- `rows` - number of rows to display in the Perch admin. Note that empty trailing rows will not appear in the final output.
- `cols` - a comma-separated list of column headers to display in the Perch admin and (optionally) in the final output.

###Optional attributes

- `limitrows` - maximum number of rows to display in the final output. By default all populated rows are displayed.
- `limitcols` - maximum number of columns to display in the final output. By default all columns are displayed.
- `noheaders` - set to "true" to remove the opening and closing `<table>` tags and the `<thead>` section from the final output (leaving just the `<tbody>` section). You are responsible for providing the remaining table markup. By default the full table is output.

##To Do

1. Do something sensible with mandatory fields. Currently saving always fails on *tabular* fields that have `required="true"` set.
2. Recover table data after form validation errors. If an error occurs when saving a region (let's say you forget to fill in a required *textarea* field), the page reloads with all of your entered data intact. But *tabular* fields revert back to their previously-saved state.

##Feedback
Contact me [@deepilla](https://twitter.com/deepilla).
