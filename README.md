# 多时间选择器
======

## ScreenShoot


## Installation

```

composer require jose-chan/multiple-datetime

php artisan vendor:publish --tag=multiple-datetime

```

## Configurations

Open `config/admin.php`, add configurations that belong to this extension at `extensions` section.

````php

    'extensions' => [

        'multiple-datetime' => [
        
            // Set to `false` if you want to disable this extension
            'enable' => true,
            
            'config' => [
                //'alias' => "setYourName",//set your AliasName Or not Set
            ]
        ]
    ]
    
````

## Usage

````php

//Use In Model's Field
$form->multipleDatetime("field", "multiple-datetime");

//Use In HasOne Relation
$form->multipleDatetime("relation->field", "multiple-datetime");

//Use In HasMany Relation
$form->multipleDatetime("relation", "multiple-datetime")->relateField("field");

````
