<?php
namespace interactivesolutions\honeycombscripts\commands;

use interactivesolutions\honeycombcore\commands\HCCommand;
use Nette\Reflection\AnnotationsParser;
use Symfony\Component\Finder\Finder;

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
     */
    public function handle ()
    {
        if('path' == null)
            $this->error('Path mus be given');

        $this->createDirectory($this->argument('path') . 'docs');         // creates one

        foreach ($this->getPhpFiles($this->argument('path')) as $parsedClass) {


            if($parsedClass->getRelativePath() != 'traits') {
                $class = new \Go\ParserReflection\ReflectionClass(array_keys(AnnotationsParser::parsePhp(file_get_contents($parsedClass)))[0]);

                $info = array([
                    'classInfo' => $this->getClassInfo($class),
                    'classType' => $this->getClassType($class),
                    'classInheritance' => $this->getClassInheritance($class),
                    'classProperties' => $this->getClassProperties($class),
                    'classMethods' => $this->getClassMethods($class)
                ]);

                $classesInfo[] = $info;
            }
        }

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

        return $info = array([
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

        $methods = array([
            'publicMethods' => array_filter($method1),
            'protectedMethods' => array_filter($method2),
            'privateMethods' => array_filter($method3)
        ]);

        return $methods;
    }

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