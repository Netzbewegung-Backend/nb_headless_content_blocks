# Add sub data processors

This guide shows how to add TypoScript-computed data — menus, record lists,
plugin output — to a Content Block's `data`.

## The problem it solves

Content Block fields hold editor-managed content. Sometimes the JSON needs
computed data: a page tree menu, a list of the latest news records, or
anything else a TYPO3 data processor can produce. Instead of writing that
in `headless.php`, wire standard data processors via TypoScript.

## Wire a sub data processor

```typoscript
tt_content.vendor_yourcontentblockelement.fields.data.dataProcessing.10 {
    dataProcessing {
        10 = menu
        10 {
            levels = 2
            as = navigation
        }
    }
}
```

What happens:

- The path addresses the `nb-content-blocks-json` processor (setup
  position `10` inside `fields.data.dataProcessing`) that the Site Set
  defines for `lib.contentBlock`.
- Inside it, `dataProcessing.` works exactly like everywhere else in
  TypoScript — any registered data processor can be used (`menu`,
  `database-query`, `files`, your own ...).
- The `as` name of each sub processor becomes a new key in `data`
  (here: `data.navigation`).

## Result

```json
{
    "data": {
        "header": "My header",
        "navigation": [
            { "title": "Home", "link": "/" },
            { "title": "Products", "link": "/products/" }
        ]
    }
}
```

The sub-processor results are merged into `data` after the Content Block
fields; the internal keys `data` and `current` are stripped from the
result first, so they cannot collide with your fields.

## Notes

- Sub data processors run with the block's `ContentObjectRenderer`
  context — `field:` references in the sub processor resolve against the
  block's record.
- This mechanism is used in production (e.g. dynamic card lists rendered
  by an additional processor with `as = cards`).
- Prefer it over [headless.php](post-process-with-headless-php.md) for
  anything TypoScript already provides.
