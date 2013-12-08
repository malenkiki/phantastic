# Phantastic!

PHP static blog generator ala Jekyll with tags, categories and blocks in native.


## How does it work?

You can use command line options or write a configuration file in YAML.

## How must I write my posts?

Like you already do with Jekyll! You write some **markdown post text files** with **YAML header** and that's all folks!

A post must have at least two properties: `layout` and `title`. The first give the name of the template to use, the second is the post's title.

So, this is a correct minimal post:

```yaml
    ---
    layout: my-template
    title: Some Very Important Heading
    ---

    # Blah Blah

    A short sentence.
```

## Tell me more about blocks…

Blocks are pieces of text written using markdown syntax.

This files are saved into their own directory.

Into template PHP files, you can call this blocks like you want, following some tags of the posts, or directly.

So, all text can be defined outside of templates.

## Tell me more about categories…

Categories are autodefined by **directories' hierarchy** used into posts' directory. But, you can also **associated this directories to other names** by using **YAML configuration file**.

To link directories to their right name, do following, for example:

```yaml
categories
    some_dir: My Category
    some_other_dir: Another Category Name
    last_butnot_the_least_dir: This Category Is A Must Have!
```

## Tell me more about tags…

Well, tags are… tags :) You can defined some tags for each of your posts into YAML header like follow:

```yaml
tags:
    - foo
    - bar
    - something
```

So, when generator runs, it creates tag pages, and a cloud tags is available!

## Custom YAML Header Properties

Yes, you can add anything you want into YAML to use it into your template.

## Tell me more!

Later… I must improved this project yet before go further into the doc, but it is functional like you can see live example here: <http://www.decouverte-patrimoine.fr/>.
