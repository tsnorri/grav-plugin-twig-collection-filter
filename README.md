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

The plugin adds one Twig filter, namely `filter_collection`. It takes the filtering predicate as an argument and returns an array with the pages that match the predicate. The third argument, which defaults to `true`, indicates whether recurse to the `children()` of the first page or pages. The filtered object may be a `Page` or a `Collection`. The accepted predicate formats are:

```php
// Comparison predicates:
// op is one of: '==', '===', '!=', '!==', '<', '>', '<=', '>='
// logical\_op is one of: 'and', 'or'
[op, 'keypath1', 'keypath2']						// Compare the values retrieved from the keypaths with `op`.
[op, logical_op, {'keypath' => val, …}, {…}, …]		// Compare the values retrieved from each keypath to each value with `logical_op` semantics.
[op, {'keypath' => val, …}, {…}, …]					// Equivalent to [op, 'and', {'keypath', => val, …}, {…}, …]
['in', 'keypath', [val1, val2, …]]					// True iff `keypath` ⊆ [val1, val2, …].
['in', 'keypath1', 'keypath2']						// True iff `keypath1` ⊆ `keypath2`.
['contains', 'keypath', [val1, val2, …]]			// True iff `keypath` ⊇ [val1, val2, …].

// Compound predicates:
// pred is a compound predicate or a comparison predicate.
// ['and', pred1, pred2, …]
// ['or', pred1, pred2, …]
// ['not', pred]
```
