<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Event;

use App\Models\User;
use Illuminate\Support\Facades\File;


class EventController extends Controller
{
    public function index()
    {
        $search = request('search');

        if($search){
            $events= Event::where([['title', 'like', '%'.$search.'%']
            ])->get();
        }else{
            $events = Event::all();
        }

        return view('welcome',['events' => $events, 'search'=>$search]);
    }
    
    public function create(){
        return view('events.create');
    }
//<< Dados do objeto // Requisição Http (vindo do arquivo view)>>>
    public function store(Request $request){
        $event = new Event;
        $event -> title=$request->title;
        $event->date=$request->date;
        $event -> city=$request->city;
        $event -> private=$request->private;
        $event -> description=$request->description;
        $event -> items=$request->items;

        if($request->hasFile('image')&& $request->file('image')->isValid()){
            $requestImage = $request ->image;
            $extension = $requestImage->extension();
            $imageName = md5($requestImage->getClientOriginalName().strtotime("now")).".".$extension;
            $requestImage->move(public_path('/img/events'), $imageName);        
            $event->image=$imageName;
        }

        $user=auth()->user();
        $event->user_id=$user->id;


        $event->save();

        return redirect('/') ->with('msg', 'Evento cadastrado com sucesso');
    }

    public function show($id){
        $event= Event::findOrFail($id);

        $user=auth()->user();
        $hasUserJoined = false;
        
        if($user){
            $userEvents = $user->eventAsParticipants->toArray();
            foreach($userEvents as $userEvent){
                if($userEvent['id']==$id){
                    $hasUserJoined=true;
                }
            }
        }
        
        $eventOwner = User::where('id', $event->user_id)->first()->toArray();

        return view('events.show', ['event'=>$event, 'eventOwner'=>$eventOwner, 'hasUserJoined'=>$hasUserJoined]);
    }

    public function dashboard(){
        $user=auth()->user();
        $events=$user->events;

        $eventAsParticipants = $user->eventAsParticipants;
        return view('events.dashboard',
                    ['events'=>$events, 'eventsasparticipant'=>$eventAsParticipants]);
    }

    public function destroy($id){
       

        $events = Event::findOrFail($id);
       
        $image_path = public_path('/img/events/'.$events->image);
        if(File::exists($image_path)){
            File::delete($image_path);
        }
       
        $events->delete();

        //File::delete(public_path('/img/events'));
        return redirect('/dashboard')->with('msg', 'Evento excluído com sucesso');
    }

    public function edit($id){
        $user = auth()->user();
        $event= Event::findOrFail($id);

        if($user->id != $event->user->id){
            return redirect('/dashboard');
        }
        return view('events.edit',['event'=>$event]);
    }
    public function update(Request $request){
        $data = $request->all();
        if($request->hasFile('image') && $request->file('image')->isValid()){
            $requestImage = $request ->image;
            $extension = $requestImage->extension();
            $imageName = md5($requestImage->getClientOriginalName().strtotime("now")).".".$extension;
            $requestImage->move(public_path('/img/events'), $imageName);        
            $data['image']=$imageName;
        }

        Event::findOrFail($request->id)->update($data);
        return redirect('/dashboard')->with('msg', 'Evento editado com sucesso!');
    }
    /**@var \App\Http\Controllers\EventController $joinEvent */
    public function joinEvent($id){
        $user = auth()->user();
        $user ->eventAsParticipants()->attach($id);
        
        $event=Event::findOrFail($id);
        
        return redirect('/dashboard')->with('msg', 'Sua presença está confirmada no evento');
    }
    
    public function leaveEvent($id){
        $user = auth()->user();
        $user ->eventAsParticipants()->detach($id);
        $event=Event::findOrFail($id);

        return redirect('/dashboard')->with('msg', 'Você saiu com sucesso do evento: '.$event->title);

    }
}
