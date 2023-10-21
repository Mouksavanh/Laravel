<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Store;
use App\Helpers\MyHelper;
use App\Models\StoreUser;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRequest;
use App\Services\UploadFileService;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\StoreController;

class StoreController extends Controller
{
    public function editStore(StoreRequest $request)
    {
    
        $discount = MyHelper::calDiscount(100000);
        return $discount;
        
        $editStore = Store::find($request->id);
        $editStore->name = $request->name;
        $editStore->email_contact = $request->email_contact;
        $editStore->phone_number = $request->phone_number;
        $editStore->address = $request->address;

        /** Save Image */
        if (isset($request['logo'])) {
            (new UploadFileService())->editUploadFileStoreLogo($request, $editStore);
        }
        $editStore->save();

        return response()->json([
            'message' => 'ອັບເດດສຳເລັດ.'
        ]);
    }
    //
    public function addStore(StoreRequest $request)
    {
        $filename = resolve(UploadFileService::class)->uploadFileStoreLogo($request);

        if ($request->hasFile('logo')) {
            $destination_path = '/images/Store/Logo';
            $imageFile = $request->file('logo'); 
            // Get just ext
            $extension = $imageFile->getClientOriginalExtension();
            // Filename to store
            $filename = 'store_logo' . '_' . time() . '.' . $extension;
            Storage::disk('public')->putFileAs($destination_path, $imageFile, $filename);
        }

       
        
        $addStore = new Store();
        $addStore->name = $request->name;
        $addStore->email_contact = $request->email_contact;
        $addStore->phone_number = $request->phone_number;
        $addStore->address = $request->address;
        $addStore->logo = $filename;
        $addStore->save();

        $addUser = new User();
        $addUser->name = $request->name;
        $addUser->email = $request->email;
        $addUser->password = $request->password;
        $addUser->save();

        /**Service UploadFile User Profile */
        $profilename = (new UploadFileService())->uploadFileUserProfiles($request);

        $addStoreUser = new StoreUser();
        $addStoreUser->store_id = $addStore->id;
        $addStoreUser->user_id = $addUser->id;
        $addStoreUser->profile = $profilename;
        $addStoreUser->save();

        $getRoleStoreAdmin = Role::where('name', 'admin')->first();
        $addUser->attachRole($getRoleStoreAdmin);
        
        return response()->json([
            'massage' => __('response.success')
        ]);
    }
    
    public function listStores (Request $request)
    {
        $listStores = Store::select(
            'stores.*'
        )->paginate($request->per_page);
    
        $listStores->transform(function($item) {
            $item['store_user'] =  StoreUser::select(
                'user.id',
                'user.name'
            )->join(
                'users as user', 
                'user.id', 
                'store_users.user_id'
            )->where('store_id', $item['id'])->get();

            return $item->format();
        });
        return response()->json([
            'stores' => $listStores
        ]);

    }
    public function deleteStore(StoreRequest $request)
    {
        $deleteStore = Store::find($request->id);
        $deleteStore->delete();
    }
}
