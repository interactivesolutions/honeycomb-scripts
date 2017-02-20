<?php
namespace interactivesolutions\honeycombscripts\commands;

use interactivesolutions\honeycombcore\commands\HCCommand;
use Nette\Reflection\AnnotationsParser;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

class HCDocs extends HCCommand
{

    //TODO create docs directory
    //TODO add js css
    //TODO get list of all php files in provided directory
    //TODO prepare docs templates
    //TODO for each file create a DOCS.html

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hc:docs {path?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates DOC.html file of the given class';

    /**
     * Execute the console command.
     *
     * @return class info
     */
    public function handle ()
    {
        if('path' == null)
            $this->error('Path mus be given');

        if (file_exists($this->argument('path'). 'docs'))
        {
            $this->info ('Deleting existing directory');
            $it = new RecursiveDirectoryIterator($this->argument('path'). 'docs', RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it,
                RecursiveIteratorIterator::CHILD_FIRST);
            foreach($files as $file) {
                if ($file->isDir()){
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($this->argument('path'). 'docs');
        }

        $this->createDirectory($this->argument('path') . 'docs');         // creates one

        $this->createWebsiteFrame($this->argument('path'));

        $this->info ('Website frame has been created');

        foreach ($this->getPhpFiles($this->argument('path')) as $parsedClass) {
            if($parsedClass->getRelativePath() != 'traits') {
                $class = new \Go\ParserReflection\ReflectionClass(array_keys(AnnotationsParser::parsePhp(file_get_contents($parsedClass)))[0]);

                $info = ([
                    'classInfo'         => $this->getClassInfo($class),
                    'classType'         => $this->getClassType($class),
                    'classInheritance'  => $this->getClassInheritance($class),
                    'classProperties'   => $this->getClassProperties($class),
                    'commandsInfo'      => $this->getCommands($class),

//                    'classMethods' => $this->getClassMethods($class)
                ]);
                $classesInfo[] = $info;
            }
        }

        $this->createDocFile($classesInfo);
    }

    /**
     * @param $classesInfo
     * @return string
     */
    public function createCommandsRow($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/commandsRow.template.txt');
        $output = '';

        foreach ($classesInfo as $value)
        {
            $field = str_replace ('{packageName}', $classesInfo[0]['classInfo']['name'], $file);
            $field = str_replace ('{className}', $value['classInfo']['className'], $field);
            $field = str_replace ('{classInheritance}', implode('&#8594', $value['classInheritance']), $field);
            $field = str_replace ('{commandName}', $value['commandsInfo']['signature'], $field);
            $field = str_replace ('{commandDescription}', $value['commandsInfo']['description'], $field);

            $output .= $field;
        }

        return $output;
    }

    public function createCommandsMenu($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/commandsMenu.template.txt');
        $output = '';

        foreach ($classesInfo as $value) {
            $field = str_replace ('{className}', $value['classInfo']['className'], $file);
            $output .= $field;
        }

        return $output;
    }

    /**
     * Create doc file
     *
     * @param $classesInfo
     */
    public function createDocFile($classesInfo)
    {
        $this->createFileFromTemplate([
            "destination"            => $this->argument('path'). 'docs/docs.html',
            "templateDestination"    => __DIR__ . '/templates/docs/docs.template.txt',
            "content" => [
                "packageName" => $classesInfo[0]['classInfo']['name'],
                "name" => $classesInfo[0]['classInfo']['name'],
                "commands" => $this->createCommandsRow($classesInfo),
                "commandsMenu" => $this->createCommandsMenu($classesInfo),
            ],
        ]);
    }

    /**
     * Create website frame
     *
     * @param $path
     */
    public function createWebsiteFrame($path){
        $fileList = [
            //assets/css
            [
                "destination" => $path . 'docs/assets/css/styles.css',
                "templateDestination" => __DIR__ . '/templates/docs/assets/css/styles.template.txt',
            ],
            //assets/js
            [
                "destination" => $path . 'docs/assets/js/main.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/js/main.template.txt',
            ],
            //assets/less
            [
                "destination" => $path . 'docs/assets/less/base.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/base.template.txt',
            ],
            [
                "destination" => $path . 'docs/assets/less/doc.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/doc.template.txt',
            ],
            [
                "destination" => $path . 'docs/assets/less/landing.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/landing.template.txt',
            ],
            [
                "destination" => $path . 'docs/assets/less/mixins.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/mixins.template.txt',
            ],
            [
                "destination" => $path . 'docs/assets/less/styles.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/styles.template.txt',
            ],
            [
                "destination" => $path . 'docs/assets/less/theme-default.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/theme-default.template.txt',
            ],
            //assets/plugins
            [
                "destination" => $path . 'docs/assets/plugins/jquery-1.12.3.min.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/jquery-1123-min.template.txt',
            ],
            //assets/plugins/bootstrap/css
            [
                "destination" => $path . 'docs/assets/plugins/bootstrap/css/bootstrap.css',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/bootstrap/css/bootstrap.template.txt',
            ],
            [
                "destination" => $path . 'docs/assets/plugins/bootstrap/css/bootstrap.min.css',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/bootstrap/css/bootstrap-min.template.txt',
            ],
            //assets/plugins/bootstrap/js
            [
                "destination" => $path . 'docs/assets/plugins/bootstrap/js/bootstrap.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/bootstrap/js/bootstrap.template.txt',
            ],
            [
                "destination" => $path . 'docs/assets/plugins/bootstrap/js/bootstrap.min.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/bootstrap/js/bootstrap-min.template.txt',
            ],
            [
                "destination" => $path . 'docs/assets/plugins/bootstrap/js/npm.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/bootstrap/js/npm.template.txt',
            ],
            //assets/plugins/prism
            [
                "destination" => $path . 'docs/assets/plugins/prism/prism.css',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/prism/prism-css.template.txt',
            ],
            [
                "destination" => $path . 'docs/assets/plugins/prism/prism.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/prism/prism-js.template.txt',
            ],
            //assets/plugins/prism/min
            [
                "destination" => $path . 'docs/assets/plugins/prism/min/prism-min.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/prism/min/prism-min.template.txt',
            ],
            //assets/plugins/jquery-scrollTo
            [
                "destination" => $path . 'docs/assets/plugins/jquery-scrollTo/jquery.scrollTo.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/jquery-scrollTo/jquery.scrollTo.template.txt',
            ],
            [
                "destination" => $path . 'docs/assets/plugins/jquery-scrollTo/jquery.scrollTo.min.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/jquery-scrollTo/jquery.scrollTo.min.template.txt',
            ],


        ];

        foreach ($fileList as $value)
            $this->createFileFromTemplate($value);

    }

    /**
     * Create Docs
     *
     * @param $classData
     */
    public function createDocs($classData)
    {
        $tPath = 'src/http/controllers/';
        $filePath = $this->argument('path');
        $this->createDirectory($this->argument('path') . 'docs');         // creates one

        $coltrollers = $this->getControllersHTML();

        $this->createFileFromTemplate([
            "destination" => $filePath . 'docs/' . $classData['classInfo']['installName'] . '/' . $classData['classInfo']['className'],
            "templateDestination" => __DIR__ . '/templates/docs/docs.template.txt',
            "content" => [
                "name" => $classData,
                "controllersSideBar" => $coltrollers['sideBar'],
                "controllersDetails" => $coltrollers['details']
            ],
        ]);
    }

    /**
     * You will need to install 'symfony/finder' package
     * URL: http://symfony.com/doc/current/components/finder.html
     *
     * Gets all php files in provided directory
     *
     * @param $directory
     * @return array
     */
    public function getPhpFiles($directory)
    {
        $finder = new Finder();
        $finder->files()->in(base_path() .'/' . $directory);

        foreach ($finder as $file)
        {
            if($file->getExtension() == 'php')
                $files[] = $file;
        }

        return $files;
    }

    public function getCommands($parsedClass)
    {
        $commandInfo = [
            'signature'     => $parsedClass->getDefaultProperties()['signature'],
            'description'   => $parsedClass->getDefaultProperties()['description'],
        ];

        return $commandInfo;
    }
    /**
     * Get class basic info
     *
     * @param $parsedClass
     * @return array
     */
    public function getClassInfo($parsedClass)
    {
        // Class info
        $name = explode("\\", $parsedClass->name)[1];                                                               // returns package name
        $gitHub = '';                                                                                               // returns git hub url
        $installName = explode("\\", $parsedClass->getName())[0]. '/' . explode("\\", $parsedClass->getName())[1];  // returns install name
        $serviceProvider = '';                                                                                      // returns service provider
        $className = $parsedClass->getShortName();                                                                  // returns class name

        return $info = ([
            'name' => $name,
            'gitHub' => $gitHub,
            'installName' => $installName,
            'serviceProvider' => $serviceProvider,
            'className' => $className
        ]);
    }

    /**
     * Gets class type
     *
     * @param $parsedClass
     * @return null|string
     */
    public function getClassType($parsedClass)
    {
        $classType = null;

        if($parsedClass->isAbstract())                                                                              // checks if class is abstract
            $classType = 'abstract';
        elseif($parsedClass->isFinal())                                                                             // checks if class is final
            $classType = 'final';

        return $classType;
    }

    /**
     * Gets class inheritance
     *
     * @param $parsedClass
     * @return array
     */
    public function getClassInheritance($parsedClass)
    {
        $className = $parsedClass->getShortName();                                                                  // returns class name

        $inheritance[] = $className;

        $parentClass = $parsedClass->getParentClass();
        while($parentClass)                                                                                         // while parent class is true
        {
            $inheritance[] = $parentClass->getShortName();                                                          // add parent class name to the inheritance array
            $parentClass = $parentClass->getParentClass();                                                          // get next parent class
        }

        return $inheritance;
    }

    /**
     * Gets class properties
     *
     * @param $parsedClass
     * @return array
     */
    public function getClassProperties($parsedClass)
    {
        $properties = null;

        foreach ($parsedClass->getProperties() as $property)
        {
            if($parsedClass->getName() == $property->class) {                                                       // check only for the required class
                if ($property->isPrivate())                                                                         // check whether class is private
                    $type = 'private';
                elseif ($property->isPublic())                                                                      // check whether class is public
                    $type = 'public';
                elseif ($property->isProtected())                                                                   // check whether class is protected
                    $type = 'protected';

                $property = [
                    'className' => $property->getDeclaringClass()->getName(),
                    'propertyName' => $property->getName(),
                    'type' => $type,
                    'declaredBy' => $property->getDeclaringClass()->getShortName(),
                    'comment' => str_replace(['     ', '/**', '*/', '* ', '*', "/\r|\n/"], '', $property->getDocComment())
                ];
                $properties[] = $property;
            }
        }

        return $properties;
    }

    /**
     * Gets class methods
     *
     * @param $parsedClass
     * @return array
     */
    public function getClassMethods($parsedClass)
    {
        foreach ($parsedClass->getMethods() as $method) {
            $method1[] = $this->getPublicMethods($method, $parsedClass);
            $method2[] = $this->getProtectedMethods($method,$parsedClass);
            $method3[] = $this->getPrivateMethods($method,$parsedClass);
        }

        $methods = ([
            'publicMethods' => array_filter($method1),
            'protectedMethods' => array_filter($method2),
            'privateMethods' => array_filter($method3)
        ]);


        return $methods;
    }

    /**
     * get public methods
     *
     * @param $method
     * @param $parsedClass
     * @return array
     */
    function getPublicMethods($method,$parsedClass){

        $filterResult = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
            $string = trim(preg_replace('/\s\s+/', ' ',
                $method->getDocComment()))), 0 , 2);

        if($parsedClass->getName() == $method->class && $method->isPublic())
        {
            if($method->getDocComment() == null){
                $comment = null;
                $params = null;
                $return = null;

            }elseif($filterResult == "@r"){
                $comment = null;
                $params = null;
                $return = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
                    $string = trim(preg_replace('/\s\s+/', ' ',
                        $method->getDocComment()))), 8);
            }
            else{
                $method_data = explode("*", $string = trim(preg_replace('/\s\s+/', ' ', str_replace(['     ', '/**', '* ', "/\r|\n/"], '', $method))), 2);
                $comment = $method_data[0];


                $rreturn = explode("*/", explode("@return", $method_data[1], 2)[1], 2);
                $return = $rreturn[0];

                $params = array_filter(explode(',', str_replace(' @param ', ',', explode(" @return ", $method_data[1], 2)[0])));
                $return = str_replace(' ', '', $return);
            }

            $post_data = array(
                'method' => $method->name,
                'param' => $params,
                'return' => $return,
                'comment' => $comment
            );

            return $post_data;
        }

    }

    /**
     * get protected methods
     *
     * @param $method
     * @param $parsedClass
     * @return array
     */
    function getProtectedMethods($method,$parsedClass){

        $filterResult = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
            $string = trim(preg_replace('/\s\s+/', ' ',
                $method->getDocComment()))), 0 , 2);

        if($parsedClass->getName() == $method->class && $method->isProtected())
        {
            if($method->getDocComment() == null){
                $comment = null;
                $params = null;
                $return = null;

            }elseif($filterResult == "@r"){
                $comment = null;
                $params = null;
                $return = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
                    $string = trim(preg_replace('/\s\s+/', ' ',
                        $method->getDocComment()))), 8);
            }else{
                $method_data = explode("*", $string = trim(preg_replace('/\s\s+/', ' ', str_replace(['     ', '/**', '* ', "/\r|\n/"], '', $method))), 2);

                $comment = $method_data[0];


                $rreturn = explode("*/", explode("@return", $method_data[1], 2)[1], 2);
                $return = $rreturn[0];

                $params = array_filter(explode(',', str_replace(' @param ', ',', explode(" @return ", $method_data[1], 2)[0])));
                $return = str_replace(' ', '', $return);

            }
            $post_data = array(
                'method' => $method->name,
                'param' => $params,
                'return' => $return,
                'comment' => $comment
            );

            return $post_data;
        }

    }

    /**
     * get private methods
     *
     * @param $method
     * @param $parsedClass
     * @return array
     */
    function getPrivateMethods($method,$parsedClass){

        $filterResult = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
            $string = trim(preg_replace('/\s\s+/', ' ',
                $method->getDocComment()))), 0 , 2);

        if($parsedClass->getName() == $method->class && $method->isPrivate())
        {
            if($method->getDocComment() == null){
                $comment = null;
                $params = null;
                $return = null;

            }elseif($filterResult == "@r"){
                $comment = null;
                $params = null;
                $return = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
                    $string = trim(preg_replace('/\s\s+/', ' ',
                        $method->getDocComment()))), 8);
            }else{
                $method_data = explode("*", $string = trim(preg_replace('/\s\s+/', ' ', str_replace(['     ', '/**', '* ', "/\r|\n/"], '', $method))), 2);
                $comment = $method_data[0];


                $rreturn = explode("*/", explode("@return", $method_data[1], 2)[1], 2);
                $return = $rreturn[0];

                $params = array_filter(explode(',', str_replace(' @param ', ',', explode(" @return ", $method_data[1], 2)[0])));
                $return = str_replace(' ', '', $return);

            }
            $post_data = array(
                'method' => $method->name,
                'param' => $params,
                'return' => $return,
                'comment' => $comment
            );

            return $post_data;
        }

    }

}