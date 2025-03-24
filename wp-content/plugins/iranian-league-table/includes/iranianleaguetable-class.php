<?php

 class Iranian_League_Widget extends WP_Widget {
  
    /**
     * Register widget with WordPress.
     */
    function __construct() {
      parent::__construct(
        'iranianleaguetable_widget', // Base ID
        esc_html__( 'Iranian League Table', 'ilt_domain' ), // Name
        array( 'description' => esc_html__( 'Widget to display Iranian leagues table', 'ilt_domain' ), ) // Args
      );
    }
  
    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
      echo $args['before_widget']; // Whatever you want to display before widget (<div>, etc)

      if ( ! empty( $instance['title'] ) ) {
        echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
      }

      // Widget Content Output
      echo '<div class="vaz3-table" data-sportype="Football" style="direction: rtl;" data-url="'.$instance['league'].'" data-lt="لیگ آزادگان" data-wv="'.$instance['table_type'].'" data-bw="0" data-bc="#eee" data-tc="#212121" data-tbgc="#eee" data-thc="#fff" data-thbgc="'.$instance['title_color'].'" data-tdc="'.$instance['text_color'].'" data-tdac="'.$instance['text_color'].'" data-oddbgc="'.$instance['odd_color'].'" data-evbgc="'.$instance['even_color'].'" data-lw="'.$instance['logo_size'].'" data-ld="'.$instance['logo'].'" data-tlv="false" data-tshv="false" data-fsh="'.$instance['font_h_size'].'" data-fsd="'.$instance['font_d_size'].'" ></div>';

      echo $args['after_widget']; // Whatever you want to display after widget (</div>, etc)
    }
  
    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {

      $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Persian Gulf League', 'ilt_domain' );
      $league = ! empty( $instance['league'] ) ? $instance['league'] : 'https://web-api.varzesh3.com/v1.0/developer-tools/football/leagues/6/standing';
      $table_type = ! empty( $instance['table_type'] ) ? $instance['table_type'] : 'basic';
      $title_color = ! empty( $instance['title_color'] ) ? $instance['title_color'] : '#33337a';
      $logo = ! empty( $instance['logo'] ) ? $instance['logo'] : 'true';
      $logo_size = ! empty( $instance['logo_size'] ) ? $instance['logo_size'] : '15';
      $font_h_size = ! empty( $instance['font_h_size'] ) ? $instance['font_h_size'] : '12';
      $font_d_size = ! empty( $instance['font_d_size'] ) ? $instance['font_d_size'] : '14';
      $text_color = ! empty( $instance['text_color'] ) ? $instance['text_color'] : '#000000';
      $odd_color = ! empty( $instance['odd_color'] ) ? $instance['odd_color'] : '#ffffff';
      $even_color = ! empty( $instance['even_color'] ) ? $instance['even_color'] : '#eeeeee';
      $border_size = ! empty( $instance['border_size'] ) ? $instance['border_size'] : '0';
  
      ?>
      
      <!-- TITLE -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
          <?php esc_attr_e( 'Title:', 'ilt_domain' ); ?>
        </label> 

        <input 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" 
          type="text" 
          value="<?php echo esc_attr( $title ); ?>">
      </p>

      <!-- LEAGUE -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'league' ) ); ?>">
          <?php esc_attr_e( 'LEAGUE NAME:', 'ilt_domain' ); ?>
        </label> 

        <select 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'league' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'league' ) ); ?>">
          <option value="https://web-api.varzesh3.com/v1.0/developer-tools/football/leagues/6/standing" <?php echo ($league == 'https://web-api.varzesh3.com/v1.0/developer-tools/football/leagues/6/standing') ? 'selected' : ''; ?>>
            <?php esc_attr_e( 'Persian Gulf', 'ilt_domain' ) ?>
          </option>
          <option value="https://web-api.varzesh3.com/v1.0/developer-tools/football/leagues/24/standing" <?php echo ($league == 'https://web-api.varzesh3.com/v1.0/developer-tools/football/leagues/24/standing') ? 'selected' : ''; ?>>
            <?php esc_attr_e( 'League One', 'ilt_domain' ) ?>
          </option>
        </select>
      </p>

      <!-- TABLE TYPE -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'table_type' ) ); ?>">
          <?php esc_attr_e( 'Table Type:', 'ilt_domain' ); ?>
        </label> 

        <select 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'table_type' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'table_type' ) ); ?>">
          <option value="basic" <?php echo ($table_type == 'basic') ? 'selected' : ''; ?>>
          <?php esc_attr_e( 'Basic', 'ilt_domain' ) ?>
          </option>
          <option value="advanced" <?php echo ($table_type == 'advanced') ? 'selected' : ''; ?>>
            <?php esc_attr_e( 'Advanced', 'ilt_domain' ) ?>
          </option>
        </select>
      </p>

      <!-- TITLE COLOR -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'title_color' ) ); ?>">
          <?php esc_attr_e( 'Title Color:', 'ilt_domain' ); ?>
        </label> 

        <input 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'title_color' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'title_color' ) ); ?>" 
          type="color" 
          value="<?php echo esc_attr( $title_color ); ?>">
      </p>

      <!-- LOGO -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'logo' ) ); ?>">
          <?php esc_attr_e( 'Show Logo:', 'ilt_domain' ); ?>
        </label> 

        <select 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'logo' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'logo' ) ); ?>">
          <option value="true" <?php echo ($logo == 'true') ? 'selected' : ''; ?>>
            <?php esc_attr_e( 'Yes', 'ilt_domain' ) ?>
          </option>
          <option value="false" <?php echo ($logo == 'false') ? 'selected' : ''; ?>>
            <?php esc_attr_e( 'No', 'ilt_domain' ) ?>
          </option>
        </select>
      </p>

      <!-- LOGO SIZE -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'logo_size' ) ); ?>">
          <?php esc_attr_e( 'Logo Size:', 'ilt_domain' ); ?>
        </label> 

        <input 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'logo_size' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'logo_size' ) ); ?>" 
          type="number" 
          value="<?php echo esc_attr( $logo_size ); ?>">
      </p>

      <!-- FONT HEAD SIZE -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'font_h_size' ) ); ?>">
          <?php esc_attr_e( 'Header Font Size:', 'ilt_domain' ); ?>
        </label> 

        <input 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'font_h_size' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'font_h_size' ) ); ?>" 
          type="number" 
          value="<?php echo esc_attr( $font_h_size ); ?>">
      </p>

      <!-- FONT Body SIZE -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'font_d_size' ) ); ?>">
          <?php esc_attr_e( 'Body Font Size:', 'ilt_domain' ); ?>
        </label> 

        <input 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'font_d_size' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'font_d_size' ) ); ?>" 
          type="number" 
          value="<?php echo esc_attr( $font_d_size ); ?>">
      </p>

      <!-- TEXT COLOR -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'text_color' ) ); ?>">
          <?php esc_attr_e( 'Text Color:', 'ilt_domain' ); ?>
        </label> 

        <input 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'text_color' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'text_color' ) ); ?>" 
          type="color" 
          value="<?php echo esc_attr( $text_color ); ?>">
      </p>

      <!-- ODD ROWS COLOR -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'odd_color' ) ); ?>">
          <?php esc_attr_e( 'Odd Rows Color:', 'ilt_domain' ); ?>
        </label> 

        <input 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'odd_color' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'odd_color' ) ); ?>" 
          type="color" 
          value="<?php echo esc_attr( $odd_color ); ?>">
      </p>

      <!-- Even ROWS COLOR -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'even_color' ) ); ?>">
          <?php esc_attr_e( 'Even Rows Color:', 'ilt_domain' ); ?>
        </label> 

        <input 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'even_color' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'even_color' ) ); ?>" 
          type="color" 
          value="<?php echo esc_attr( $even_color ); ?>">
      </p>

      <!-- BORDER SIZE -->
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'border_size' ) ); ?>">
          <?php esc_attr_e( 'Border Size:', 'ilt_domain' ); ?>
        </label> 

        <input 
          class="widefat" 
          id="<?php echo esc_attr( $this->get_field_id( 'border_size' ) ); ?>" 
          name="<?php echo esc_attr( $this->get_field_name( 'border_size' ) ); ?>" 
          type="number" 
          value="<?php echo esc_attr( $border_size ); ?>">
      </p>
      <?php 
    }
  
    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
      $instance = array();

      $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
      $instance['league'] = ( ! empty( $new_instance['league'] ) ) ? strip_tags( $new_instance['league'] ) : '';
      $instance['table_type'] = ( ! empty( $new_instance['table_type'] ) ) ? strip_tags( $new_instance['table_type'] ) : '';
      $instance['title_color'] = ( ! empty( $new_instance['title_color'] ) ) ? strip_tags( $new_instance['title_color'] ) : '';
      $instance['logo'] = ( ! empty( $new_instance['logo'] ) ) ? strip_tags( $new_instance['logo'] ) : '';
      $instance['logo_size'] = ( ! empty( $new_instance['logo_size'] ) ) ? strip_tags( $new_instance['logo_size'] ) : '';
      $instance['font_h_size'] = ( ! empty( $new_instance['font_h_size'] ) ) ? strip_tags( $new_instance['font_h_size'] ) : '';
      $instance['font_d_size'] = ( ! empty( $new_instance['font_d_size'] ) ) ? strip_tags( $new_instance['font_d_size'] ) : '';
      $instance['text_color'] = ( ! empty( $new_instance['text_color'] ) ) ? strip_tags( $new_instance['text_color'] ) : '';
      $instance['odd_color'] = ( ! empty( $new_instance['odd_color'] ) ) ? strip_tags( $new_instance['odd_color'] ) : '';
      $instance['even_color'] = ( ! empty( $new_instance['even_color'] ) ) ? strip_tags( $new_instance['even_color'] ) : '';
      $instance['border_size'] = ( ! empty( $new_instance['border_size'] ) ) ? strip_tags( $new_instance['border_size'] ) : '';
  
      return $instance;
    }
  
  }