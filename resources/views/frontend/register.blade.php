@extends('frontend.layout.app')

@section('content')
<section class="vh-80 gradient-custom">
    <div class="container py-5 h-100">
      <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
          <div class="card bg-light" style="border-radius: 1rem;">
            <div class="card-body p-md-4 p-4 text-center">
  
                <form action="{{ route('register.post') }}" method="POST">
                    @csrf
                    <div class="mb-md-4 mt-md-2 pb-4">
        
                        <h2 class="fw-bold mb-2 text-uppercase">Register</h2>
                        <p class=" mb-5">Please enter your detail!</p>
        
                        <div class="form-outline mb-4">
                            <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="Name" required />
                        </div>

                        <div class="form-outline mb-4">
                            <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="Email" required />
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="form-outline mb-4">
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Password" required />
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="form-outline mb-4">
                                    <input type="password" class="form-control form-control-lg" id="password_confirmation" name="password_confirmation" placeholder="Confirm Password" required />
                                </div>
                            </div>
                        </div>
        
                        

                        

                        <div class="form-outline mb-4">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" required />
                            <label class="form-check-label" for="remember">Remember Me</label>
                        </div>
                
                        <button class="btn btn_outline_secondary btn-lg px-5 w-100 d-flex justify-content-center" type="submit">Submit</button>
        
                    </div>
        
                    <div>
                        <p class="mb-0">Already have an account? <a href="{{ route('login.post') }}" class=" fw-bold">Login</a>
                        </p>
                    </div>
                </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection


