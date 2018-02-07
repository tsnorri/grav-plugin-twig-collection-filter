# Twig Collection Filter Plugin

The **Twig Collection Filter** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It may be used to filter Grav collections.

## Installation

Installing the Twig Collection Filter plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install twig-collection-filter

This will install the Twig Collection Filter plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/twig-collection-filter`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `twig-collection-filter`. You can find these files on [GitHub](https://github.com/tsnorri/grav-plugin-twig-collection-filter) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/twig-collection-filter
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/twig-collection-filter/twig-collection-filter.yaml` to `user/config/plugins/twig-collection-filter.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
```

## Usage

The plugin adds three Twig filters, namely `filter_collection`, `test_predicate` and `csort`.

### `filter_collection`

`filter_collection` may be applied to a `Page` or a `Collection`. It takes the filtering predicate as an argument and returns an array with the pages that match the predicate. The third argument, which defaults to `true`, indicates whether to recurse to the `children()` of the given page or pages in the given collection.

### `test_predicate`

`test_predicate` may be applied to a `Page`. It takes the filtering predicate as an argument and returns `true` if the page matches the predicate.

### `csort`

`csort` sorts an array by the given key path.

### Predicate formats

The accepted predicate formats are:

```php
// Comparison predicates:
// op is one of: '==', '===', '!=', '!==', '<', '>', '<=', '>='
// logical\_op is one of: 'and', 'or'
// Each of keypath, keypath1, keypath2 are strings that contain a sequence of attribute names separated by periods.
// Suppose obj is the filtered object.
[op, keypath1, keypath2]											// True iff obj.keypath1 op obj.keypath2.
[op, logical_op, {keypath1 => val1, keypath2 => val2, …}, {…}, …]	// True iff (obj.keypath1 op val1) logical\_op (obj.keypath2 op val2) logical\_op …
[op, {keypath => val, …}, {…}, …]									// Equivalent to [op, 'and', {keypath, => val, …}, {…}, …]
['is\_null', keypath]												// True iff is_null(obj.keypath)
['in', keypath, [val1, val2, …]]									// True iff obj.keypath ⊆ [val1, val2, …].
['in', keypath1, keypath2]											// True iff obj.keypath1 ⊆ obj.keypath2.
['contains', keypath, [val1, val2, …]]								// Equivalent to ['contains', 'all', keypath, [val1, val2, …]]
['contains', 'all', keypath, [val1, val2, …]]						// True iff obj.keypath ⊇ [val1, val2, …].
['contains', 'any', keypath, [val1, val2, …]]						// True iff obj.keypath ∩ [val1, val2, …] ≠ ∅.

// Compound predicates:
// Each of pred, pred1, pred2 may be a compound predicates or a comparison predicate.
// ['and', pred1, pred2, …]
// ['or', pred1, pred2, …]
// ['not', pred]
```

### Example

Return the pages in the collection of the current page that have the keyword `wrapper` in the `layout\_option` taxonomy:

    page.collection()|filter_collection(['contains', 'taxonomy.layout_option', ['wrapper']])
