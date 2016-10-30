<?php
/**
 *
 * Cart Printer Controller
 * Copyright © 2016 Voicyou Softwares. All rights reserved.
 */
namespace Voicyou\CartPrinter\Controller\Cart;
require_once getcwd()."/app/code/Voicyou/CartPrinter/tcpdf/tcpdf.php";
use TCPDF;
class Printer extends \Magento\Framework\App\Action\Action
{
    /**
     * Store Manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * Cart Model
     *
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * Currency Model
     *
     * @var \Magento\Directory\Model\Currency
     */
    protected $currency;
    /**
     * Store Switcher Block
     *
     * @var \Magento\Backend\Block\Store\Switcher\Interceptor
     */
    protected $interceptor;
    /**
     * Store Scope Config Interface
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Checkout\Model\Cart $cart,
       \Magento\Store\Model\StoreManagerInterface $storeManager,
       \Magento\Directory\Model\Currency $currency,
       \Magento\Backend\Block\Store\Switcher\Interceptor $interceptor,
       \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->currency = $currency;
        $this->interceptor = $interceptor;
        $this->scopeConfig = $scopeConfig;
    }
    public function execute()
    {
       //die($this->scopeConfig->getValue('cartprinter/general/header_text', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
       $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 
       $pdf->setTitle('Voicyou Cart Printer');
       // set default header data
       $pdf->SetHeaderData('', '', $this->scopeConfig->getValue('cartprinter/general/header_text', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), '', array(0,64,255), array(0,64,128));
       $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
       $currencySymb = $this->currency->getCurrencySymbol();
       $allCartItems = $this->cart->getItems();
       $cartTr = "";
       $totalPrice = 0;
       $configItemQty = 0;
       foreach($allCartItems as $cartItem)
       {
        if($cartItem->getProduct()->getTypeId())
        {   // we should not show the main parent product
           $itemQty        = $cartItem->getQty();
           if($cartItem->getProduct()->getTypeId() == 'configurable') // all the simple product data that is associated to the configurable is stored in the configurable product object
           {
                $configItemQty   = $cartItem->getQty();
                continue;
           }
           if ($configItemQty != 0)
           {
               $itemQty = $configItemQty;
               $configItemQty = 0;
           }
           $itemFinalPrice = round($cartItem->getProduct()->getFinalPrice(),2);
           $itemSubTotal   = round($itemQty*$itemFinalPrice,2);
           $totalPrice     = $totalPrice+$itemSubTotal;
           $placeholderImg = $this->interceptor->getViewFileUrl('Magento_Catalog::images/product/placeholder/small_image.jpg');
           if ($cartItem->getProduct()->getSmallImage() == '')
           {
               $productImageUrl = $placeholderImg;
           }
           else
           {
               $productImageUrl = $this->_url->getUrl('pub/media/catalog').'product'.$cartItem->getProduct()->getSmallImage();
           }
           $cartTr = $cartTr.'<tr style="line-height: 135px;"><td><img src = "'.$productImageUrl.'" width = "100px" height = "100px" /></td><td>'.$cartItem->getProduct()->getName().'</td><td>'.$currencySymb.$itemFinalPrice.'</td><td>'.$itemQty.'</td><td>'.$currencySymb.$itemSubTotal.'</td></tr>';
        }

       }

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('times', '', 10);

        // add a page
        $pdf->AddPage();

        // set cell padding
        $pdf->setCellPaddings(1, 1, 1, 1);

        // set cell margins
        $pdf->setCellMargins(1, 1, 1, 1);
        
        if($cartTr != '')
        {
            $cartEndData = "<h4  >Total: ".$currencySymb.$totalPrice."</h4>";
            $table = "<table  width = '100%' ><tr><th>Image</th><th>Name</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr>";
            $html = "<html><body>".$table.$cartTr."</table>".$cartEndData."</body></html>";            
        }
        else
        {
            $html = "<h3>Your Cart Is Empty.</h3>";
        }

        $pdf->writeHTML($html, true, false, true, false, '');
        // move pointer to last page
        $pdf->lastPage();
        //Close and output PDF document
        $pdf->Output('yourcartdata.pdf', 'I');
    }
    
}