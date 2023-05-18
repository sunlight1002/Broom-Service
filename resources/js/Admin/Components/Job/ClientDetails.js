import React  from 'react'

export default function ClientDetails({client}) {
  return (
    <>
                    <h2 className="text-custom">Client Details</h2>
                    <div className='dashBox p-4 mb-3'>
                        <form>
                            <div className='row'>
                                <div className='col-sm-6'>
                                    <div className='form-group'>
                                        <label className='control-label'>Client Name</label>
                                          <p>{client.firstname} {client.lastname}</p>
                                    </div>
                                </div>
                                <div className='col-sm-6'>
                                    <div className='form-group'>
                                        <label className='control-label'>Client Email</label>
                                          <p>{client.email}</p>
                                    </div>
                                </div>
                                <div className='col-sm-6'>
                                    <div className='form-group'>
                                        <label className='control-label'>Client Phone</label>
                                         <p><a href={`tel:${client.phone}`}>{client.phone}</a></p>
                                    </div>
                                </div>
                                <div className='col-sm-4'>
                                    <div className='form-group'>
                                        <label className='control-label'>City</label>
                                         <p>{client.city}</p>
                                       
                                    </div>  </div>
                                <div className='col-sm-8'>
                                    <div className='form-group'>
                                        <label className='control-label'>Address</label>
                                         <p>{client.geo_address}</p>
                                    </div>
                                </div>
                               
                        </div>
                </form>
            </div>
        </>
 )
}
