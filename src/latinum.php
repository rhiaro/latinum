<?
namespace rhiaro\latinum;

use rhiaro\ERH;
use EasyRdf_Graph;
use EasyRdf_Resource;
use EasyRdf_Literal;

// Things for displaying the form

function get_tags(){
    $tags = array(
        "groceries" => "https://rhiaro.co.uk/tags/groceries",
        "food" => "https://rhiaro.co.uk/tags/food",
        "restaurant" => "https://rhiaro.co.uk/tags/restaurant",
        "takeaway" => "https://rhiaro.co.uk/tags/takeaway",
        "transit" => "https://rhiaro.co.uk/tags/transit",
        "transport" => "https://rhiaro.co.uk/tags/transport",
        "travel" => "https://rhiaro.co.uk/tags/travel",
        "shelter" => "https://rhiaro.co.uk/tags/shelter",
        "accommodation" => "https://rhiaro.co.uk/tags/accommodation",
        "utilities" => "https://rhiaro.co.uk/tags/utilities",
        "gift" => "https://rhiaro.co.uk/tags/gift",
        "donation" => "https://rhiaro.co.uk/tags/donation",
    );
    return $tags;
}

// Form input processing

function make_cost($cost){
    if(ERH\valid_string($cost)){
        if($cost == "0"){
            $cost = "0EUR";
        }
        return $cost;
    }else{
        return false;
    }
}

function make_payload($form_request){
    $g = new EasyRdf_Graph();
    $errors = array();

    $published_date_parts = [
        "year" => $form_request["year"],
        "month" => $form_request["month"],
        "day" => $form_request["day"],
        "time" => $form_request["time"],
        "zone" => $form_request["zone"],
    ];
    $published_date = ERH\make_xsd_date($published_date_parts);

    $tags = ERH\make_tags($form_request["tags"], "https://rhiaro.co.uk/tags/");
    $content = trim($form_request["content"]);
    $cost = make_cost($form_request["cost"]);

    if($cost === false){
        $errors["cost"] = "invalid cost: <code>".$form_request["cost"]."</code>";
        unset($cost);
    }
    if(ERH\valid_string($form_request["amountEur"])){
        $eur = $form_request["amountEur"];
    }
    if(ERH\valid_string($form_request["amountUsd"])){
        $usd = $form_request["amountUsd"];
    }
    if(ERH\valid_string($form_request["amountGbp"])){
        $gbp = $form_request["amountGbp"];
    }

    if(!ERH\valid_string($content)){
        $errors["content"] = "empty value not allowed";
    }

    if(empty($errors)){
        $node = $g->newBNode();
        $g->addType($node, "as:Activity");
        $g->addType($node, "asext:Acquire");
        $g->addLiteral($node, "as:published", $published_date);
        $g->addLiteral($node, "as:content", $content);
        $g->addLiteral($node, "asext:cost", $cost);
        if(isset($eur)){
            $g->addLiteral($node, "asext:amountEur", $eur);
        }
        if(isset($usd)){
            $g->addLiteral($node, "asext:amountUsd", $usd);
        }
        if(isset($gbp)){
            $g->addLiteral($node, "asext:amountGbp", $gbp);
        }
        $g->addResource($node, "as:generator", "https://apps.rhiaro.co.uk/latinum");
        foreach($tags as $tag){
            $g->addResource($node, "as:tag", $tag);
        }

        return ERH\make_jsonld_payload($g);

    }else{
        return $errors;
    }
}


?>