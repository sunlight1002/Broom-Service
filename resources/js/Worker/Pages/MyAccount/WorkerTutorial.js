import React from 'react'
import VideoGalleryPage from '../../../Pages/Form101/VideoGalleryPage'
import WorkerSidebar from '../../Layouts/WorkerSidebar'

const WorkerTutorial = () => {
    return (
        <div id="container">
            <WorkerSidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row px-2">
                        <VideoGalleryPage forms={false}/>
                    </div>
                </div>
            </div>
        </div>
    )
}

export default WorkerTutorial